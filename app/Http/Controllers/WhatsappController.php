<?php

namespace App\Http\Controllers;

use App\Models\WhatsappSession;
use App\Services\WahaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsappController extends Controller
{
    public function __construct(
        protected WahaService $waha,
    ) {}

    public function index(Request $request): View
    {
        $session = $request->user()->whatsappSession;

        return view('whatsapp.index', [
            'session' => $session,
        ]);
    }

    public function connect(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionName = 'user_' . $user->id;

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
            'qr' => $qr ? 'data:' . $qr['mimetype'] . ';base64,' . $qr['image'] : null,
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

        return response()->json(['ok' => true]);
    }
}
