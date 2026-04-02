<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendChatRequest;
use App\Services\OpenAIService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    protected const STREAM_HEADERS = [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache',
        'Connection' => 'keep-alive',
        'X-Accel-Buffering' => 'no',
    ];

    public function index(): View
    {
        $credential = auth()->user()->openaiCredential;

        return view('chat.index', [
            'hasCredential' => $credential && $credential->hasValidCredential(),
            'credential' => $credential,
        ]);
    }

    public function send(SendChatRequest $request): StreamedResponse
    {
        $user = auth()->user();
        $validated = $request->validated();

        try {
            $service = OpenAIService::forUser($user);
        } catch (\RuntimeException $e) {
            return $this->streamError($e->getMessage());
        }

        $messages = $validated['history'] ?? [];
        $messages[] = ['role' => 'user', 'content' => $validated['message']];
        $model = $validated['model'] ?? null;

        return new StreamedResponse(function () use ($service, $messages, $model) {
            try {
                foreach ($service->streamChatCompletion($messages, $model) as $chunk) {
                    echo 'data: '.json_encode(['content' => $chunk])."\n\n";
                    ob_flush();
                    flush();
                }
                echo "data: [DONE]\n\n";
                ob_flush();
                flush();
            } catch (\Exception $e) {
                echo 'data: '.json_encode(['error' => $e->getMessage()])."\n\n";
                ob_flush();
                flush();
            }
        }, 200, self::STREAM_HEADERS);
    }

    protected function streamError(string $message): StreamedResponse
    {
        return new StreamedResponse(function () use ($message) {
            echo 'data: '.json_encode(['error' => $message])."\n\n";
            ob_flush();
            flush();
        }, 200, self::STREAM_HEADERS);
    }
}
