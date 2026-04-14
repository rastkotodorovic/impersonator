<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium text-gray-900">WhatsApp</h3>
                        <p class="mt-2 text-sm text-gray-500">Connect a WAHA-backed WhatsApp session and manage auto-reply contacts.</p>
                        <a href="{{ route('whatsapp.index') }}"
                           class="mt-4 inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                            Open WhatsApp
                        </a>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium text-gray-900">Message Import</h3>
                        <p class="mt-2 text-sm text-gray-500">Upload Facebook Messenger, Instagram, or WhatsApp exports, import the history into PostgreSQL, and rebuild embeddings automatically.</p>
                        <a href="{{ route('imports.index') }}"
                           class="mt-4 inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            Open Imports
                        </a>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium text-gray-900">AI</h3>
                        <p class="mt-2 text-sm text-gray-500">Configure separate providers for chat completions and embeddings, including OpenAI, Claude, and Voyage.</p>
                        <a href="{{ route('ai.index') }}"
                           class="mt-4 inline-flex items-center rounded-md bg-black px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-800">
                            Open Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
