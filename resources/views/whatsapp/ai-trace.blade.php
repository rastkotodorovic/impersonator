<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    AI Trace Inspector
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $trace->contact_phone }} · {{ ucfirst($trace->status) }} · {{ $trace->created_at->diffForHumans() }}
                </p>
            </div>
            <a href="{{ route('whatsapp.auto-reply.index') }}"
               class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                Back to Auto-Reply
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">Incoming Message</h3>
                        <p class="mt-3 whitespace-pre-wrap rounded-lg bg-gray-50 p-4 text-sm text-gray-800">{{ $trace->input_message }}</p>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">Response Summary</h3>
                        <dl class="mt-4 space-y-3 text-sm text-gray-700">
                            <div class="flex justify-between gap-4">
                                <dt>Status</dt>
                                <dd class="font-medium">{{ ucfirst($trace->status) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt>Model</dt>
                                <dd class="font-medium">{{ $trace->model ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt>Latency</dt>
                                <dd class="font-medium">{{ $trace->latency_ms ? $trace->latency_ms . ' ms' : '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt>Retrieved Hits</dt>
                                <dd class="font-medium">{{ count($trace->retrieval_hits ?? []) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt>Prompt Messages</dt>
                                <dd class="font-medium">{{ count($trace->final_prompt ?? []) }}</dd>
                            </div>
                        </dl>

                        @if($trace->usage)
                            <div class="mt-6 rounded-lg bg-gray-50 p-4">
                                <h4 class="text-sm font-semibold text-gray-900">Token Usage</h4>
                                <dl class="mt-2 space-y-1 text-sm text-gray-700">
                                    <div class="flex justify-between gap-4">
                                        <dt>Prompt</dt>
                                        <dd>{{ $trace->usage['prompt_tokens'] ?? '—' }}</dd>
                                    </div>
                                    <div class="flex justify-between gap-4">
                                        <dt>Completion</dt>
                                        <dd>{{ $trace->usage['completion_tokens'] ?? '—' }}</dd>
                                    </div>
                                    <div class="flex justify-between gap-4">
                                        <dt>Total</dt>
                                        <dd>{{ $trace->usage['total_tokens'] ?? '—' }}</dd>
                                    </div>
                                </dl>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">Generated Reply</h3>
                        @if($trace->error)
                            <p class="mt-3 whitespace-pre-wrap rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ $trace->error }}</p>
                        @else
                            <p class="mt-3 whitespace-pre-wrap rounded-lg bg-green-50 p-4 text-sm text-gray-800">{{ $trace->model_response ?: 'No response recorded.' }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">Recent WhatsApp Context</h3>
                        @if(empty($trace->recent_conversation))
                            <p class="mt-3 text-sm text-gray-500">No recent conversation was included.</p>
                        @else
                            <div class="mt-4 space-y-3">
                                @foreach($trace->recent_conversation as $message)
                                    <div class="rounded-lg {{ ($message['role'] ?? 'user') === 'assistant' ? 'bg-green-50' : 'bg-blue-50' }} p-3">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $message['role'] ?? 'unknown' }}</div>
                                        <p class="mt-1 whitespace-pre-wrap text-sm text-gray-800">{{ $message['content'] ?? '' }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">Retrieved Matches</h3>
                        @if(empty($trace->retrieval_hits))
                            <p class="mt-3 text-sm text-gray-500">No historical matches were retrieved.</p>
                        @else
                            <div class="mt-4 space-y-3">
                                @foreach($trace->retrieval_hits as $hit)
                                    <div class="rounded-lg border border-gray-200 p-4">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900">#{{ $hit['rank'] ?? '?' }} · {{ $hit['conversation_title'] ?? 'Conversation' }}</p>
                                                <p class="mt-1 text-xs text-gray-500">{{ $hit['sender_name'] ?? 'Unknown' }} · {{ $hit['sent_at'] ?? '—' }}</p>
                                            </div>
                                            <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs text-gray-700">
                                                Score: {{ isset($hit['ranking_score']) ? number_format((float) $hit['ranking_score'], 3) : '—' }}
                                            </span>
                                        </div>
                                        <p class="mt-3 whitespace-pre-wrap text-sm text-gray-800">{{ $hit['content'] ?? '' }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Context Snippet Windows</h3>
                    @if(empty($trace->context_snippets))
                        <p class="mt-3 text-sm text-gray-500">No snippet windows were captured.</p>
                    @else
                        <div class="mt-4 space-y-5">
                            @foreach($trace->context_snippets as $snippet)
                                <div class="rounded-lg border border-gray-200 p-4">
                                    <div class="flex items-center justify-between gap-4">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $snippet['conversation_title'] ?? 'Conversation' }}</p>
                                            <p class="mt-1 text-xs text-gray-500">
                                                Anchor: {{ $snippet['matched_message']['sender_name'] ?? 'Unknown' }}
                                                @if(isset($snippet['matched_message']['ranking_score']))
                                                    · score {{ number_format((float) $snippet['matched_message']['ranking_score'], 3) }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                    <div class="mt-4 space-y-2">
                                        @foreach($snippet['messages'] ?? [] as $message)
                                            <div class="rounded-md bg-gray-50 p-3">
                                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $message['sender_name'] ?? 'Unknown' }}</div>
                                                <p class="mt-1 whitespace-pre-wrap text-sm text-gray-800">{{ $message['content'] ?? '' }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Final Prompt</h3>
                    @if(empty($trace->final_prompt))
                        <p class="mt-3 text-sm text-gray-500">Prompt data was not captured.</p>
                    @else
                        <div class="mt-4 space-y-3">
                            @foreach($trace->final_prompt as $message)
                                <div class="rounded-lg {{ ($message['role'] ?? 'system') === 'system' ? 'bg-amber-50' : (($message['role'] ?? 'user') === 'assistant' ? 'bg-green-50' : 'bg-blue-50') }} p-4">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $message['role'] ?? 'unknown' }}</div>
                                    <p class="mt-2 whitespace-pre-wrap text-sm text-gray-800">{{ $message['content'] ?? '' }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
