<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Import Message History') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-md bg-green-50 p-4">
                    <p class="text-sm font-medium text-green-800">Import finished</p>
                    <p class="mt-1 text-sm text-green-700">{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-md bg-red-50 p-4">
                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                </div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="overflow-hidden rounded-lg bg-indigo-600 p-5 text-white shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-indigo-100">Library Messages</p>
                    <p class="mt-3 text-3xl font-semibold">{{ number_format($totalMessages) }}</p>
                    <p class="mt-1 text-sm text-indigo-100">Total messages currently available for retrieval.</p>
                </div>

                <div class="overflow-hidden rounded-lg bg-white p-5 text-gray-900 shadow-sm ring-1 ring-gray-200">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-gray-500">Conversations</p>
                    <p class="mt-3 text-3xl font-semibold">{{ number_format($totalConversations) }}</p>
                    <p class="mt-1 text-sm text-gray-500">Distinct imported message threads.</p>
                </div>

                <div class="overflow-hidden rounded-lg bg-white p-5 text-gray-900 shadow-sm ring-1 ring-gray-200">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-gray-500">Last Absorbed</p>
                    <p class="mt-3 text-3xl font-semibold">{{ number_format($latestRun?->messages_imported ?? 0) }}</p>
                    <p class="mt-1 text-sm text-gray-500">Messages imported in the latest run.</p>
                </div>

                <div class="overflow-hidden rounded-lg bg-white p-5 text-gray-900 shadow-sm ring-1 ring-gray-200">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-gray-500">Last Skipped</p>
                    <p class="mt-3 text-3xl font-semibold">{{ number_format($latestRun?->messages_skipped ?? 0) }}</p>
                    <p class="mt-1 text-sm text-gray-500">Non-text or placeholder entries ignored in the latest run.</p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1.2fr,0.8fr]">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium text-gray-900">Upload Message Export</h3>
                        <p class="mt-2 text-sm text-gray-500">
                            Upload a Facebook Messenger or Instagram messages ZIP export, or a WhatsApp single-chat export `.txt` or `.zip`. You can also point the app at an already extracted local export path. The app imports the message history into PostgreSQL and rebuilds embeddings immediately.
                        </p>

                        <form method="POST" action="{{ route('imports.store') }}" enctype="multipart/form-data" class="mt-6 space-y-4" x-data="{ submitting: false }" @submit="submitting = true">
                            @csrf

                            <div>
                                <label for="archive" class="block text-sm font-medium text-gray-700">Export file</label>
                                <input type="file" name="archive" id="archive" accept=".zip,.txt"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                       >
                                <p class="mt-2 text-xs text-gray-500">Works with Facebook Messenger and Instagram ZIP archives, plus WhatsApp exported chat `.txt` or `.zip` files. For very large exports, use the local path field below instead.</p>
                                @error('archive')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="source_path" class="block text-sm font-medium text-gray-700">Local export path</label>
                                <input type="text" name="source_path" id="source_path"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                       value="{{ old('source_path') }}"
                                       placeholder="data/your_facebook_activity/messages, data/your_instagram_activity/messages, or data/whatsapp/_chat.txt">
                                <p class="mt-2 text-xs text-gray-500">Best for very large exports. Point this field to `your_facebook_activity/messages`, `your_instagram_activity/messages`, a WhatsApp `_chat.txt`, or a ZIP file path on this machine.</p>
                                @error('source_path')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="me_name" class="block text-sm font-medium text-gray-700">Your name in the export</label>
                                <input type="text" name="me_name" id="me_name"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                       value="{{ old('me_name', 'Rastko Todorovic') }}"
                                       placeholder="Your full name as shown in the export" required>
                                <p class="mt-2 text-xs text-gray-500">Used to mark which imported messages are yours across Facebook Messenger, Instagram, or WhatsApp exports.</p>
                                @error('me_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <label class="flex items-start gap-3 rounded-md border border-gray-200 p-4">
                                <input type="checkbox" name="replace_existing" value="1"
                                       class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                       @checked(old('replace_existing'))>
                                <span>
                                    <span class="block text-sm font-medium text-gray-900">Replace existing imported history</span>
                                    <span class="mt-1 block text-sm text-gray-500">Deletes current `conversations` and `messages` data before importing the new export, then rebuilds embeddings from scratch.</span>
                                </span>
                            </label>

                            <button type="submit"
                                    class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                                    x-bind:disabled="submitting"
                                    @disabled($isImportRunning)>
                                <svg x-show="submitting" class="-ml-0.5 mr-2 h-4 w-4 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                <span x-show="!submitting">{{ $isImportRunning ? 'Import In Progress' : 'Start Import' }}</span>
                                <span x-show="submitting">Uploading and importing...</span>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <h3 class="text-lg font-medium text-gray-900">Latest Import</h3>

                            @if(! $latestRun)
                                <p class="mt-4 text-sm text-gray-500">No message imports have been run yet.</p>
                            @else
                                <div class="mt-4 rounded-lg bg-slate-50 p-4 ring-1 ring-slate-200">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-sm font-medium text-slate-900">{{ $latestRun->uploaded_filename }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ $latestRun->created_at->diffForHumans() }}</p>
                                        </div>
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $latestRun->status === 'failed' ? 'bg-red-100 text-red-700' : ($latestRun->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-indigo-100 text-indigo-700') }}">{{ ucfirst($latestRun->status) }}</span>
                                    </div>

                                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                        <div class="rounded-md bg-white p-3 ring-1 ring-gray-200">
                                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Absorbed</p>
                                            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($latestRun->messages_imported ?? 0) }}</p>
                                        </div>
                                        <div class="rounded-md bg-white p-3 ring-1 ring-gray-200">
                                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Skipped</p>
                                            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($latestRun->messages_skipped ?? 0) }}</p>
                                        </div>
                                        <div class="rounded-md bg-white p-3 ring-1 ring-gray-200">
                                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Convers.</p>
                                            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($latestRun->conversations_count ?? 0) }}</p>
                                        </div>
                                    </div>
                                </div>

                                @if($latestRun->error)
                                    <div class="mt-4 rounded-md bg-red-50 p-4">
                                        <p class="text-sm text-red-700">{{ $latestRun->error }}</p>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>

                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <h3 class="text-lg font-medium text-gray-900">Recent Runs</h3>

                            @if($recentRuns->isEmpty())
                                <p class="mt-4 text-sm text-gray-500">No recent imports yet.</p>
                            @else
                                <div class="mt-4 space-y-3">
                                    @foreach($recentRuns as $run)
                                        <div class="rounded-md border border-gray-200 p-3">
                                            <div class="flex items-center justify-between gap-4">
                                                <span class="truncate text-sm font-medium text-gray-900">{{ $run->uploaded_filename }}</span>
                                                <span class="text-xs {{ $run->status === 'failed' ? 'text-red-600' : ($run->status === 'completed' ? 'text-green-600' : 'text-indigo-600') }}">{{ ucfirst($run->status) }}</span>
                                            </div>
                                            <p class="mt-1 text-xs text-gray-500">{{ $run->created_at->diffForHumans() }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900">How To Export</h3>

                    <div class="mt-4 grid gap-6 lg:grid-cols-2">
                        <div class="rounded-lg border border-gray-200 p-4">
                            <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-900">Facebook Messenger</h4>
                            <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-gray-600">
                                <li>Open Facebook and go to <span class="font-medium text-gray-900">Profile</span> then <span class="font-medium text-gray-900">Settings &amp; privacy</span>.</li>
                                <li>Open <span class="font-medium text-gray-900">Accounts Center</span>, then go to <span class="font-medium text-gray-900">Your information and permissions</span> and choose <span class="font-medium text-gray-900">Download your information</span>.</li>
                                <li>Start a new export and deselect everything except <span class="font-medium text-gray-900">Messages</span>.</li>
                                <li>Choose <span class="font-medium text-gray-900">JSON</span>, not HTML.</li>
                                <li>Create the export, download the ZIP, and upload it here or point to `your_facebook_activity/messages`.</li>
                            </ol>
                        </div>

                        <div class="rounded-lg border border-gray-200 p-4">
                            <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-900">Instagram</h4>
                            <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-gray-600">
                                <li>Open Instagram and go to <span class="font-medium text-gray-900">Settings and activity</span>.</li>
                                <li>Open <span class="font-medium text-gray-900">Accounts Center</span>, then <span class="font-medium text-gray-900">Your information and permissions</span> and choose <span class="font-medium text-gray-900">Download your information</span>.</li>
                                <li>Create an export that includes <span class="font-medium text-gray-900">Messages</span>.</li>
                                <li>Choose <span class="font-medium text-gray-900">JSON</span>, not HTML.</li>
                                <li>Download the ZIP, then upload it here or point to `your_instagram_activity/messages`.</li>
                            </ol>
                        </div>

                        <div class="rounded-lg border border-gray-200 p-4 lg:col-span-2">
                            <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-900">WhatsApp Single Chat</h4>
                            <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-gray-600">
                                <li>Open the WhatsApp conversation you want to reuse in the app.</li>
                                <li>Choose <span class="font-medium text-gray-900">Export chat</span>.</li>
                                <li>Export <span class="font-medium text-gray-900">without media</span> for the simplest upload, or include media if you want WhatsApp to give you a ZIP archive.</li>
                                <li>Upload the exported `.txt` or `.zip` file here, or point the local path field at the exported `_chat.txt` file.</li>
                            </ol>
                        </div>
                    </div>

                    <div class="mt-6 rounded-md bg-gray-50 p-4 text-sm text-gray-600">
                        <p class="font-medium text-gray-900">What gets imported</p>
                        <p class="mt-1">Facebook and Instagram imports read only message data from `inbox`, `e2ee_cutover`, and `message_requests`. Other account data in the archive is ignored.</p>
                        <p class="mt-2">Instagram attachment placeholders like “sent an attachment” are skipped so they do not pollute retrieval context.</p>
                        <p class="mt-2">WhatsApp exported chat system notices and media placeholders like `&lt;Media omitted&gt;` are skipped for the same reason.</p>
                        <p class="mt-2">If the full export is too large to upload through the browser, extract it locally and use the local path field instead.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
