<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Telegram Bot') }}
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
                    <h3 class="text-lg font-medium text-gray-900">Connect Telegram</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Add your Telegram bot token. The app registers a webhook and uses that bot for auto-replies.
                    </p>

                    @if($bot?->isConnected())
                        <div class="mt-4 rounded-md bg-blue-50 p-4">
                            <p class="text-sm text-blue-700">
                                Connected as <span class="font-semibold">{{ '@'.$bot->bot_username }}</span>
                            </p>
                        </div>

                        <form method="POST" action="{{ route('telegram.disconnect') }}" class="mt-4">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                                Disconnect Telegram
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('telegram.connect') }}" class="mt-4 space-y-4">
                            @csrf
                            <div>
                                <label for="bot_token" class="block text-sm font-medium text-gray-700">Bot Token</label>
                                <input type="password" name="bot_token" id="bot_token"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm"
                                       placeholder="123456:ABC-DEF..." required>
                                @error('bot_token')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit"
                                    class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">
                                Connect Telegram
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900">Whitelisting</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Add Telegram usernames or numeric chat IDs on the Auto-Reply page and choose the Telegram channel.
                    </p>
                </div>
            </div>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900">Recent Telegram Activity</h3>

                    @if($recentLogs->isEmpty())
                        <p class="mt-4 text-sm text-gray-500">No Telegram messages yet.</p>
                    @else
                        <div class="mt-4 space-y-3">
                            @foreach($recentLogs as $log)
                                <div class="rounded-md {{ $log->error ? 'bg-red-50' : ($log->direction === 'incoming' ? 'bg-blue-50' : 'bg-green-50') }} p-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-medium {{ $log->direction === 'incoming' ? 'text-blue-700' : 'text-green-700' }}">
                                            {{ $log->direction === 'incoming' ? 'Received from' : 'Replied to' }} {{ $log->contact_name ?? $log->contact_identifier }}
                                        </span>
                                        <span class="text-xs text-gray-500">{{ $log->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-700">{{ \Illuminate\Support\Str::limit($log->body, 200) }}</p>
                                    @if($log->error)
                                        <p class="mt-1 text-xs text-red-600">Error: {{ $log->error }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
