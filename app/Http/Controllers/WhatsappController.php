<?php

namespace App\Http\Controllers;

use App\Integrations\Waha\WahaService;
use App\Jobs\ProcessWhatsappAutoReply;
use App\Models\AutoReplyContact;
use App\Models\WhatsappMessageLog;
use App\Models\WhatsappSession;
use App\Services\Ai\ChatProviderManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WhatsappController extends Controller
{
    public function __construct(
        protected WahaService $waha,
        protected ChatProviderManager $chatProviders,
    ) {}

    public function index(Request $request): Response
    {
        $session = $request->user()->whatsappSession;

        return Inertia::render('Whatsapp/Index', [
            'session' => $session ? [
                'status' => $session->status,
                'phone_number' => $session->phone_number,
                'display_name' => $session->display_name,
                'connected_at' => $session->connected_at?->diffForHumans(),
            ] : null,
            'urls' => [
                'dashboard' => route('dashboard'),
                'profile' => route('profile.edit'),
                'whatsapp' => route('whatsapp.index'),
                'connect' => route('whatsapp.connect'),
                'qrCode' => route('whatsapp.qr-code'),
                'status' => route('whatsapp.status'),
                'disconnect' => route('whatsapp.disconnect'),
                'autoReply' => route('whatsapp.auto-reply.index'),
                'imports' => route('imports.index'),
                'ai' => route('ai.index'),
            ],
        ]);
    }

    public function connect(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionName = config('services.waha.session_name', 'default');

        $session = WhatsappSession::updateOrCreate(
            ['user_id' => $user->id],
            [
                'session_name' => $sessionName,
                'status' => 'qr_pending',
            ]
        );

        $webhookUrl = 'http://laravel.test/webhooks/whatsapp';
        $result = $this->waha->startSession($sessionName, $webhookUrl);

        return response()->json([
            'success' => true,
            'session_name' => $sessionName,
            'waha_status' => $result['status'] ?? 'STARTING',
        ]);
    }

    public function qrCode(Request $request): JsonResponse
    {
        $session = $request->user()->whatsappSession;

        if (! $session) {
            return response()->json(['error' => 'No session found'], 404);
        }

        $wahaStatus = $this->waha->getSessionStatus($session->session_name);

        if ($wahaStatus === 'WORKING') {
            $session->update([
                'status' => 'connected',
                'connected_at' => now(),
            ]);

            return response()->json([
                'status' => 'connected',
                'qr' => null,
            ]);
        }

        if ($wahaStatus !== 'SCAN_QR_CODE') {
            return response()->json([
                'status' => $wahaStatus,
                'qr' => null,
            ]);
        }

        $qr = $this->waha->getQrCode($session->session_name);

        return response()->json([
            'status' => 'scan_qr',
            'qr' => $qr ? 'data:'.$qr['mimetype'].';base64,'.$qr['image'] : null,
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $session = $request->user()->whatsappSession;

        if (! $session) {
            return response()->json(['status' => 'not_configured']);
        }

        $wahaStatus = $this->waha->getSessionStatus($session->session_name);

        $mappedStatus = match ($wahaStatus) {
            'WORKING' => 'connected',
            'SCAN_QR_CODE' => 'qr_pending',
            'STARTING' => 'qr_pending',
            'STOPPED' => 'disconnected',
            'FAILED' => 'failed',
            default => $session->status,
        };

        if ($mappedStatus !== $session->status) {
            $updateData = ['status' => $mappedStatus];
            if ($mappedStatus === 'connected') {
                $updateData['connected_at'] = now();
            }
            $session->update($updateData);
        }

        return response()->json([
            'status' => $mappedStatus,
            'phone_number' => $session->phone_number,
            'display_name' => $session->display_name,
            'connected_at' => $session->connected_at?->diffForHumans(),
        ]);
    }

    public function disconnect(Request $request): JsonResponse
    {
        $session = $request->user()->whatsappSession;

        if (! $session) {
            return response()->json(['error' => 'No session found'], 404);
        }

        $this->waha->logoutSession($session->session_name);
        $this->waha->stopSession($session->session_name);

        $session->update([
            'status' => 'disconnected',
            'disconnected_at' => now(),
            'phone_number' => null,
            'display_name' => null,
        ]);

        return response()->json(['success' => true]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $event = $request->input('event');
        $sessionName = $request->input('session');
        $payload = $request->input('payload', []);

        if ($event === 'session.status') {
            $session = WhatsappSession::where('session_name', $sessionName)->first();

            if ($session) {
                $wahaStatus = $payload['status'] ?? null;
                $mappedStatus = match ($wahaStatus) {
                    'WORKING' => 'connected',
                    'SCAN_QR_CODE' => 'qr_pending',
                    'STOPPED' => 'disconnected',
                    'FAILED' => 'failed',
                    default => $session->status,
                };

                $updateData = ['status' => $mappedStatus];
                if ($mappedStatus === 'connected') {
                    $updateData['connected_at'] = now();
                }

                $session->update($updateData);
            }
        }

        if ($event === 'message') {
            \Log::info('WAHA message webhook', $request->all());
            $this->handleIncomingMessage($sessionName, $payload);
        }

        return response()->json(['ok' => true]);
    }

    protected function handleIncomingMessage(string $sessionName, array $payload): void
    {
        // Skip messages sent by us (prevent infinite loop)
        if ($payload['fromMe'] ?? false) {
            return;
        }

        $session = WhatsappSession::where('session_name', $sessionName)->first();

        if (! $session || ! $session->isConnected()) {
            return;
        }

        $user = $session->user;

        // WAHA NOWEB uses LID addressing; real phone is in _data.key.remoteJidAlt
        $from = $payload['_data']['key']['remoteJidAlt']
            ?? $payload['from']
            ?? null;
        $body = $payload['body'] ?? '';
        $messageId = $payload['id'] ?? null;

        if (! $from || ! $body) {
            return;
        }

        // Check for duplicate message
        if ($messageId && WhatsappMessageLog::where('waha_message_id', $messageId)->exists()) {
            return;
        }

        // Normalize: strip @s.whatsapp.net, @c.us, or @lid suffix
        $normalizedPhone = preg_replace('/@.*$/', '', $from);
        $contact = AutoReplyContact::where('user_id', $user->id)
            ->where('channel', 'whatsapp')
            ->where('identifier', $normalizedPhone)
            ->where('is_active', true)
            ->first();

        if (! $contact) {
            return;
        }

        // Check user has a configured chat provider credential
        if (! $this->chatProviders->hasConfiguredProvider($user)) {
            return;
        }

        // Rate limit: skip if we replied to this contact in the last 5 seconds
        $recentReply = WhatsappMessageLog::where('user_id', $user->id)
            ->where('contact_phone', $normalizedPhone)
            ->where('direction', 'outgoing')
            ->where('created_at', '>', now()->subSeconds(5))
            ->exists();

        if ($recentReply) {
            return;
        }

        ProcessWhatsappAutoReply::dispatch(
            $user->id,
            $normalizedPhone,
            $body,
            $sessionName,
            $messageId,
        );
    }
}
