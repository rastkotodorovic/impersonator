<?php

namespace App\Http\Controllers;

use App\Models\AiTrace;
use App\Models\WhatsappMessageLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiTraceController extends Controller
{
    public function show(Request $request, WhatsappMessageLog $log): Response
    {
        abort_unless($log->user_id === $request->user()->id, 403);

        $trace = AiTrace::query()
            ->with(['incomingLog', 'outgoingLog'])
            ->where('outgoing_whatsapp_message_log_id', $log->id)
            ->firstOrFail();

        return Inertia::render('Whatsapp/AiTrace', [
            'log' => [
                'id' => $log->id,
                'contact_phone' => $log->contact_phone,
                'direction' => $log->direction,
                'body' => $log->body,
                'context_messages_used' => $log->context_messages_used,
                'error' => $log->error,
            ],
            'trace' => [
                'id' => $trace->id,
                'contact_phone' => $trace->contact_phone,
                'status' => $trace->status,
                'created_at' => $trace->created_at?->diffForHumans(),
                'created_at_full' => $trace->created_at?->toDayDateTimeString(),
                'input_message' => $trace->input_message,
                'retrieval_query' => $trace->retrieval_query,
                'model' => $trace->model,
                'model_response' => $trace->model_response,
                'latency_ms' => $trace->latency_ms,
                'error' => $trace->error,
                'retrieval_hits_count' => count($trace->retrieval_hits ?? []),
                'prompt_messages_count' => count($trace->final_prompt ?? []),
                'usage' => $trace->usage ? [
                    'prompt_tokens' => $trace->usage['prompt_tokens'] ?? null,
                    'completion_tokens' => $trace->usage['completion_tokens'] ?? null,
                    'total_tokens' => $trace->usage['total_tokens'] ?? null,
                ] : null,
                'recent_conversation' => collect($trace->recent_conversation ?? [])
                    ->map(fn (array $message) => [
                        'role' => $message['role'] ?? 'unknown',
                        'content' => $message['content'] ?? '',
                    ])
                    ->values()
                    ->all(),
                'retrieval_hits' => collect($trace->retrieval_hits ?? [])
                    ->map(fn (array $hit) => [
                        'rank' => $hit['rank'] ?? null,
                        'conversation_title' => $hit['conversation_title'] ?? 'Conversation',
                        'sender_name' => $hit['sender_name'] ?? 'Unknown',
                        'sent_at' => $hit['sent_at'] ?? null,
                        'ranking_score' => $hit['ranking_score'] ?? null,
                        'content' => $hit['content'] ?? '',
                    ])
                    ->values()
                    ->all(),
                'context_snippets' => collect($trace->context_snippets ?? [])
                    ->map(fn (array $snippet) => [
                        'conversation_title' => $snippet['conversation_title'] ?? 'Conversation',
                        'matched_message' => [
                            'sender_name' => $snippet['matched_message']['sender_name'] ?? 'Unknown',
                            'ranking_score' => $snippet['matched_message']['ranking_score'] ?? null,
                        ],
                        'messages' => collect($snippet['messages'] ?? [])
                            ->map(fn (array $message) => [
                                'sender_name' => $message['sender_name'] ?? 'Unknown',
                                'content' => $message['content'] ?? '',
                            ])
                            ->values()
                            ->all(),
                    ])
                    ->values()
                    ->all(),
                'final_prompt' => collect($trace->final_prompt ?? [])
                    ->map(fn (array $message) => [
                        'role' => $message['role'] ?? 'unknown',
                        'content' => $message['content'] ?? '',
                    ])
                    ->values()
                    ->all(),
            ],
            'urls' => [
                'dashboard' => route('dashboard'),
                'profile' => route('profile.edit'),
                'whatsapp' => route('whatsapp.index'),
                'autoReply' => route('whatsapp.auto-reply.index'),
                'imports' => route('imports.index'),
                'ai' => route('ai.index'),
            ],
        ]);
    }
}
