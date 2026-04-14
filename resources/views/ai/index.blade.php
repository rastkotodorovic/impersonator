<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('AI Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-md bg-green-50 p-4">
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900">Provider Selection</h3>
                    <p class="mt-2 text-sm text-gray-500">Choose the provider used for chat replies and the provider used for embeddings and retrieval.</p>

                    <form method="POST" action="{{ route('ai.providers.update') }}" class="mt-6 grid gap-4 md:grid-cols-2">
                        @csrf
                        <div>
                            <label for="chat_provider" class="block text-sm font-medium text-gray-700">Chat provider</label>
                            <select name="chat_provider" id="chat_provider" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="openai" @selected($chatProvider === 'openai')>OpenAI</option>
                                <option value="anthropic" @selected($chatProvider === 'anthropic')>Claude (Anthropic)</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Used for auto-reply generation and chat completions.</p>
                        </div>
                        <div>
                            <label for="embedding_provider" class="block text-sm font-medium text-gray-700">Embedding provider</label>
                            <select name="embedding_provider" id="embedding_provider" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="openai" @selected($embeddingProvider === 'openai')>OpenAI</option>
                                <option value="voyage" @selected($embeddingProvider === 'voyage')>Voyage AI</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Used for message embeddings and retrieval queries.</p>
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                                Save Provider Preferences
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @include('ai.partials.setup')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
