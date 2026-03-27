<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Chat') }}
            </h2>
            @if($hasCredential)
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-500">
                        @if($credential->isApiKey())
                            API Key: sk-...{{ substr($credential->getActiveToken(), -4) }}
                        @else
                            {{ $credential->openai_email ?? 'OpenAI Account' }}
                        @endif
                    </span>
                    <form method="POST" action="{{ route('openai.credential.destroy') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs text-red-600 hover:text-red-800">Disconnect</button>
                    </form>
                </div>
            @endif
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mx-auto mt-4 max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-md bg-green-50 p-3">
                <p class="text-sm text-green-700">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if($hasCredential)
        {{-- Chat Interface --}}
        <div x-data="chatApp()" class="flex flex-col" style="height: calc(100vh - 130px);">

            {{-- Model Selector --}}
            <div class="border-b bg-white px-4 py-2">
                <select x-model="selectedModel" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="gpt-4o">GPT-4o</option>
                    <option value="gpt-4o-mini">GPT-4o mini</option>
                    <option value="o3-mini">o3-mini</option>
                    <option value="gpt-4.1">GPT-4.1</option>
                    <option value="gpt-4.1-mini">GPT-4.1 mini</option>
                    <option value="gpt-4.1-nano">GPT-4.1 nano</option>
                </select>
            </div>

            {{-- Messages Area --}}
            <div class="flex-1 overflow-y-auto p-4" x-ref="messagesContainer">
                <div class="mx-auto max-w-3xl space-y-4">
                    {{-- Empty State --}}
                    <div x-show="messages.length === 0 && !isStreaming" class="flex h-full items-center justify-center pt-20">
                        <div class="text-center text-gray-400">
                            <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                            </svg>
                            <p class="mt-2 text-sm">Send a message to start chatting</p>
                        </div>
                    </div>

                    {{-- Message Bubbles --}}
                    <template x-for="(msg, index) in messages" :key="index">
                        <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                            <div :class="msg.role === 'user'
                                    ? 'bg-indigo-600 text-white rounded-2xl rounded-br-md'
                                    : 'bg-gray-100 text-gray-900 rounded-2xl rounded-bl-md'"
                                 class="max-w-[75%] px-4 py-2.5 text-sm">
                                <div x-show="msg.role === 'user'" x-text="msg.content"></div>
                                <div x-show="msg.role === 'assistant'" class="prose prose-sm max-w-none" x-html="renderMarkdown(msg.content)"></div>
                            </div>
                        </div>
                    </template>

                    {{-- Streaming Message --}}
                    <div x-show="isStreaming" class="flex justify-start">
                        <div class="max-w-[75%] rounded-2xl rounded-bl-md bg-gray-100 px-4 py-2.5 text-sm text-gray-900">
                            <div x-show="streamingContent" class="prose prose-sm max-w-none" x-html="renderMarkdown(streamingContent)"></div>
                            <div x-show="!streamingContent" class="flex items-center gap-1">
                                <div class="h-2 w-2 animate-bounce rounded-full bg-gray-400" style="animation-delay: 0ms"></div>
                                <div class="h-2 w-2 animate-bounce rounded-full bg-gray-400" style="animation-delay: 150ms"></div>
                                <div class="h-2 w-2 animate-bounce rounded-full bg-gray-400" style="animation-delay: 300ms"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Error Message --}}
                    <div x-show="errorMessage" class="flex justify-center">
                        <div class="rounded-md bg-red-50 px-4 py-2 text-sm text-red-700" x-text="errorMessage"></div>
                    </div>
                </div>
            </div>

            {{-- Input Area --}}
            <div class="border-t bg-white p-4">
                <div class="mx-auto max-w-3xl">
                    <form @submit.prevent="sendMessage()" class="flex items-end gap-3">
                        <textarea x-model="newMessage"
                                  x-ref="messageInput"
                                  @keydown.enter.exact.prevent="sendMessage()"
                                  @input="autoResize($event)"
                                  rows="1"
                                  class="flex-1 resize-none rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                  style="max-height: 200px"
                                  placeholder="Type a message..."
                                  :disabled="isStreaming"></textarea>
                        <button type="submit"
                                :disabled="isStreaming || !newMessage.trim()"
                                class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
        <script>
            function chatApp() {
                return {
                    messages: [],
                    newMessage: '',
                    isStreaming: false,
                    streamingContent: '',
                    errorMessage: '',
                    selectedModel: '{{ config('services.openai.default_model', 'gpt-4o') }}',

                    renderMarkdown(text) {
                        if (!text) return '';
                        if (typeof marked !== 'undefined') {
                            return marked.parse(text);
                        }
                        return text.replace(/\n/g, '<br>');
                    },

                    autoResize(event) {
                        const el = event.target;
                        el.style.height = 'auto';
                        el.style.height = el.scrollHeight + 'px';
                    },

                    scrollToBottom() {
                        this.$nextTick(() => {
                            const container = this.$refs.messagesContainer;
                            container.scrollTop = container.scrollHeight;
                        });
                    },

                    async sendMessage() {
                        const message = this.newMessage.trim();
                        if (!message || this.isStreaming) return;

                        this.newMessage = '';
                        this.errorMessage = '';
                        this.$refs.messageInput.style.height = 'auto';

                        this.messages.push({ role: 'user', content: message });
                        this.scrollToBottom();

                        this.isStreaming = true;
                        this.streamingContent = '';

                        try {
                            const history = this.messages.slice(0, -1).map(m => ({
                                role: m.role,
                                content: m.content,
                            }));

                            const response = await fetch('{{ route('chat.send') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'text/event-stream',
                                },
                                body: JSON.stringify({
                                    message: message,
                                    history: history,
                                    model: this.selectedModel,
                                }),
                            });

                            if (!response.ok) {
                                throw new Error('Request failed with status ' + response.status);
                            }

                            const reader = response.body.getReader();
                            const decoder = new TextDecoder();

                            while (true) {
                                const { done, value } = await reader.read();
                                if (done) break;

                                const chunk = decoder.decode(value, { stream: true });
                                const lines = chunk.split('\n');

                                for (const line of lines) {
                                    const trimmed = line.trim();
                                    if (!trimmed || !trimmed.startsWith('data: ')) continue;

                                    const data = trimmed.slice(6);
                                    if (data === '[DONE]') continue;

                                    try {
                                        const parsed = JSON.parse(data);

                                        if (parsed.error) {
                                            this.errorMessage = parsed.error;
                                            break;
                                        }

                                        if (parsed.content) {
                                            this.streamingContent += parsed.content;
                                            this.scrollToBottom();
                                        }
                                    } catch (e) {
                                        // Skip malformed JSON lines
                                    }
                                }
                            }

                            if (this.streamingContent) {
                                this.messages.push({
                                    role: 'assistant',
                                    content: this.streamingContent,
                                });
                            }
                        } catch (error) {
                            this.errorMessage = 'Failed to send message. Please try again.';
                            console.error('Chat error:', error);
                        }

                        this.isStreaming = false;
                        this.streamingContent = '';
                        this.scrollToBottom();
                        this.$refs.messageInput.focus();
                    },
                };
            }
        </script>
    @else
        {{-- Setup Screen --}}
        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        @include('chat.partials.setup')
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-app-layout>
