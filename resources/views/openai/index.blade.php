<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('OpenAI Settings') }}
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
                    <h3 class="text-lg font-medium text-gray-900">Current Connection</h3>

                    @if($hasCredential)
                        <div class="mt-4 flex flex-col gap-4 rounded-lg border border-green-200 bg-green-50 p-5 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-sm font-medium text-green-800">Connected</p>
                                @if($credential->isApiKey())
                                    <p class="mt-2 text-sm text-green-700">Using API key `sk-...{{ substr($credential->getActiveToken(), -4) }}`</p>
                                @else
                                    <p class="mt-2 text-sm text-green-700">Signed in as `{{ $credential->openai_email ?? 'OpenAI Account' }}`</p>
                                    @if($credential->oauth_token_expires_at)
                                        <p class="mt-1 text-xs text-green-700">Token expires {{ $credential->oauth_token_expires_at->diffForHumans() }}</p>
                                    @endif
                                @endif
                            </div>

                            <form method="POST" action="{{ route('openai.credential.destroy') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center rounded-md border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700 shadow-sm hover:bg-red-50">
                                    Disconnect
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-5">
                            <p class="text-sm font-medium text-gray-900">No OpenAI credential connected</p>
                            <p class="mt-2 text-sm text-gray-500">Add an API key or sign in with OpenAI to enable reply generation and embeddings.</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @include('openai.partials.setup')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
