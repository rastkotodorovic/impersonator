<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Auto-Reply Contacts') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8 space-y-6">

            {{-- Success message --}}
            @if(session('success'))
                <div class="rounded-md bg-green-50 p-4">
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            @endif

            {{-- Add Contact Form --}}
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Add Contact</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Add a WhatsApp number or Telegram username/chat ID to auto-reply to.
                    </p>

                    <form method="POST" action="{{ route('whatsapp.auto-reply.contacts.store') }}" class="mt-4 flex items-end gap-4">
                        @csrf
                        <div class="flex-1">
                            <label for="channel" class="block text-sm font-medium text-gray-700">Channel</label>
                            <select name="channel" id="channel"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
                                <option value="whatsapp" @selected(old('channel', 'whatsapp') === 'whatsapp')>WhatsApp</option>
                                <option value="telegram" @selected(old('channel') === 'telegram')>Telegram</option>
                            </select>
                            @error('channel')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex-1">
                            <label for="identifier" class="block text-sm font-medium text-gray-700">Identifier</label>
                            <input type="text" name="identifier" id="identifier"
                                   placeholder="381651234567 or @username"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                                   value="{{ old('identifier') }}" required>
                            @error('identifier')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex-1">
                            <label for="name" class="block text-sm font-medium text-gray-700">Name (optional)</label>
                            <input type="text" name="name" id="name"
                                   placeholder="e.g. Mom"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                                   value="{{ old('name') }}">
                        </div>
                        <button type="submit"
                                class="inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                            Add
                        </button>
                    </form>
                </div>
            </div>

            {{-- Contacts List --}}
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Whitelisted Contacts</h3>

                    @if($contacts->isEmpty())
                        <p class="mt-4 text-sm text-gray-500">No contacts added yet.</p>
                    @else
                        <div class="mt-4 overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Channel</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Identifier</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($contacts as $contact)
                                        <tr>
                                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">{{ $contact->channelLabel() }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 text-sm font-mono text-gray-900">{{ $contact->identifier }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ $contact->name ?? '—' }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                                @if($contact->is_active)
                                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Active</span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">Paused</span>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                                <form method="POST" action="{{ route('whatsapp.auto-reply.contacts.toggle', $contact) }}" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-900">
                                                        {{ $contact->is_active ? 'Pause' : 'Enable' }}
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('whatsapp.auto-reply.contacts.destroy', $contact) }}" class="inline ml-3">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-sm text-red-600 hover:text-red-900">Remove</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Recent Activity Log --}}
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Recent Activity</h3>

                    @if($recentLogs->isEmpty())
                        <p class="mt-4 text-sm text-gray-500">No messages yet.</p>
                    @else
                        <div class="mt-4 space-y-3">
                            @foreach($recentLogs as $log)
                                <div class="rounded-md {{ $log->error ? 'bg-red-50' : ($log->direction === 'incoming' ? 'bg-blue-50' : 'bg-green-50') }} p-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-medium {{ $log->direction === 'incoming' ? 'text-blue-700' : 'text-green-700' }}">
                                            {{ ucfirst($log->channel ?? 'whatsapp') }} · {{ $log->direction === 'incoming' ? 'Received from' : 'Replied to' }} {{ $log->contact_phone ?? $log->contact_identifier }}
                                        </span>
                                        <span class="text-xs text-gray-500">{{ $log->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-700">{{ \Illuminate\Support\Str::limit($log->body, 200) }}</p>
                                    @if($log->error)
                                        <p class="mt-1 text-xs text-red-600">Error: {{ $log->error }}</p>
                                    @endif
                                    @if($log->context_messages_used)
                                        <p class="mt-1 text-xs text-gray-400">{{ $log->context_messages_used }} context messages used</p>
                                    @endif
                                    @if(($log->channel ?? 'whatsapp') === 'whatsapp' && $log->aiTrace)
                                        <div class="mt-3">
                                            <a href="{{ route('whatsapp.auto-reply.logs.trace', $log) }}"
                                               class="inline-flex items-center rounded-md bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-700">
                                                Visualize AI details
                                            </a>
                                        </div>
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
