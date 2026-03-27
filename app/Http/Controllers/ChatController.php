<?php

namespace App\Http\Controllers;

use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function index()
    {
        $credential = auth()->user()->openaiCredential;

        return view('chat.index', [
            'hasCredential' => $credential && $credential->hasValidCredential(),
            'credential' => $credential,
        ]);
    }

    public function send(Request $request): StreamedResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:10000'],
            'history' => ['array'],
            'history.*.role' => ['required', 'string', 'in:user,assistant,system'],
            'history.*.content' => ['required', 'string'],
            'model' => ['nullable', 'string'],
        ]);

        $user = auth()->user();

        try {
            $service = OpenAIService::forUser($user);
        } catch (\RuntimeException $e) {
            return new StreamedResponse(function () use ($e) {
                echo 'data: '.json_encode(['error' => $e->getMessage()])."\n\n";
                ob_flush();
                flush();
            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ]);
        }

        $messages = $request->input('history', []);
        $messages[] = ['role' => 'user', 'content' => $request->input('message')];
        $model = $request->input('model');

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
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
