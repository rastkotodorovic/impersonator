<div class="space-y-6">
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-lg border border-gray-200 p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-medium text-gray-900">OpenAI</h3>
                    <p class="mt-2 text-sm text-gray-500">Supports chat completions and embeddings. You can use an API key or OpenAI account sign-in for this provider.</p>
                </div>
                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $hasOpenAiCredential ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                    {{ $hasOpenAiCredential ? 'Connected' : 'Not connected' }}
                </span>
            </div>

            @if($hasOpenAiCredential)
                <div class="mt-4 rounded-lg bg-green-50 p-4 text-sm text-green-800">
                    @if($openAiCredential->isApiKey())
                        Using API key `sk-...{{ substr($openAiCredential->getActiveToken(), -4) }}`
                    @else
                        Signed in as `{{ $openAiCredential->external_email ?? 'OpenAI Account' }}`
                    @endif
                </div>
            @endif

            <form method="POST" action="{{ route('ai.openai.api-key.store') }}" class="mt-4">
                @csrf
                <label class="block text-sm font-medium text-gray-700">API key</label>
                <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                    <input type="password" name="api_key" placeholder="sk-..." required class="flex-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:shrink-0">Save</button>
                </div>
                @error('api_key')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </form>

            <form method="POST" action="{{ route('ai.openai.models.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="openai_chat_model" class="block text-sm font-medium text-gray-700">Chat model</label>
                    <input type="text" name="chat_model" id="openai_chat_model"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                           value="{{ old('chat_model', $openAiChatModel) }}"
                           placeholder="gpt-4o">
                    <p class="mt-1 text-xs text-gray-500">Used when OpenAI is the selected chat provider.</p>
                    @error('chat_model')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="openai_embedding_model" class="block text-sm font-medium text-gray-700">Embedding model</label>
                    <input type="text" name="embedding_model" id="openai_embedding_model"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                           value="{{ old('embedding_model', $openAiEmbeddingModel) }}"
                           placeholder="text-embedding-3-small">
                    <p class="mt-1 text-xs text-gray-500">Used when OpenAI handles retrieval embeddings.</p>
                    @error('embedding_model')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    Save OpenAI Models
                </button>
            </form>

            <a href="{{ route('ai.openai.redirect') }}"
               class="mt-4 inline-flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                Sign in with OpenAI
            </a>

            @if($hasOpenAiCredential)
                <form method="POST" action="{{ route('ai.openai.credential.destroy') }}" class="mt-4">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center justify-center rounded-md border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700 shadow-sm hover:bg-red-50">
                        Disconnect OpenAI
                    </button>
                </form>
            @endif
        </div>

        <div class="rounded-lg border border-gray-200 p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-medium text-gray-900">Claude</h3>
                    <p class="mt-2 text-sm text-gray-500">Anthropic chat provider for reply generation.</p>
                </div>
                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $hasAnthropicCredential ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                    {{ $hasAnthropicCredential ? 'Connected' : 'Not connected' }}
                </span>
            </div>

            <form method="POST" action="{{ route('ai.anthropic.api-key.store') }}" class="mt-4">
                @csrf
                <label class="block text-sm font-medium text-gray-700">API key</label>
                <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                    <input type="password" name="anthropic_api_key" placeholder="Anthropic API key" required class="flex-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:shrink-0">Save</button>
                </div>
                @error('anthropic_api_key')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </form>

            <form method="POST" action="{{ route('ai.anthropic.models.store') }}" class="mt-4">
                @csrf
                <label for="anthropic_chat_model" class="block text-sm font-medium text-gray-700">Chat model</label>
                <input type="text" name="chat_model" id="anthropic_chat_model"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                       value="{{ old('chat_model', $anthropicChatModel) }}"
                       placeholder="claude-3-7-sonnet-latest">
                <p class="mt-1 text-xs text-gray-500">Used when Claude is the selected chat provider.</p>
                @error('chat_model')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror

                <button type="submit" class="mt-4 inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    Save Claude Model
                </button>
            </form>

            @if($hasAnthropicCredential)
                <form method="POST" action="{{ route('ai.anthropic.credential.destroy') }}" class="mt-4">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center justify-center rounded-md border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700 shadow-sm hover:bg-red-50">
                        Disconnect Claude
                    </button>
                </form>
            @endif
        </div>

        <div class="rounded-lg border border-gray-200 p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-medium text-gray-900">Voyage</h3>
                    <p class="mt-2 text-sm text-gray-500">Embedding provider for message indexing and retrieval.</p>
                </div>
                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $hasVoyageCredential ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                    {{ $hasVoyageCredential ? 'Connected' : 'Not connected' }}
                </span>
            </div>

            <form method="POST" action="{{ route('ai.voyage.api-key.store') }}" class="mt-4">
                @csrf
                <label class="block text-sm font-medium text-gray-700">API key</label>
                <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                    <input type="password" name="voyage_api_key" placeholder="Voyage API key" required class="flex-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:shrink-0">Save</button>
                </div>
                @error('voyage_api_key')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </form>

            <form method="POST" action="{{ route('ai.voyage.models.store') }}" class="mt-4">
                @csrf
                <label for="voyage_embedding_model" class="block text-sm font-medium text-gray-700">Embedding model</label>
                <input type="text" name="embedding_model" id="voyage_embedding_model"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                       value="{{ old('embedding_model', $voyageEmbeddingModel) }}"
                       placeholder="voyage-3-lite">
                <p class="mt-1 text-xs text-gray-500">Used when Voyage is the selected embedding provider.</p>
                @error('embedding_model')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror

                <button type="submit" class="mt-4 inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    Save Voyage Model
                </button>
            </form>

            @if($hasVoyageCredential)
                <form method="POST" action="{{ route('ai.voyage.credential.destroy') }}" class="mt-4">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center justify-center rounded-md border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700 shadow-sm hover:bg-red-50">
                        Disconnect Voyage
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
