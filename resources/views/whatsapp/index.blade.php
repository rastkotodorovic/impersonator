<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('WhatsApp Connection') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- State: Disconnected / Not configured --}}
                    <div id="disconnected-container" class="{{ ($session && !in_array($session->status, ['disconnected', 'failed'])) ? 'hidden' : '' }}">
                        <div class="text-center">
                            <svg class="mx-auto h-16 w-16 text-green-500" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-gray-900">Connect WhatsApp</h3>
                            <p class="mt-2 text-sm text-gray-500">
                                Link your WhatsApp account by scanning a QR code, just like WhatsApp Web.
                            </p>

                            @if($session && $session->status === 'failed')
                                <div class="mt-4 rounded-md bg-red-50 p-3">
                                    <p class="text-sm text-red-700">Connection failed. Please try again.</p>
                                </div>
                            @endif

                            <button id="connect-btn"
                                    class="mt-6 inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600">
                                Connect WhatsApp
                            </button>
                        </div>
                    </div>

                    {{-- State: QR Code Pending --}}
                    <div id="qr-container" class="{{ (!$session || $session->status !== 'qr_pending') ? 'hidden' : '' }}">
                        <div class="text-center">
                            <h3 class="text-lg font-medium text-gray-900">Scan QR Code</h3>
                            <p class="mt-2 text-sm text-gray-500">
                                Open WhatsApp on your phone &rarr; Settings &rarr; Linked Devices &rarr; Link a Device
                            </p>

                            <div class="mt-6 flex justify-center">
                                <div id="qr-loading" class="flex h-64 w-64 items-center justify-center rounded-lg border-2 border-dashed border-gray-300">
                                    <svg class="h-8 w-8 animate-spin text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                                <img id="qr-image" class="hidden h-64 w-64 rounded-lg" src="" alt="WhatsApp QR Code">
                            </div>

                            <p class="mt-4 text-xs text-gray-400">QR code refreshes automatically</p>

                            <button id="cancel-btn"
                                    class="mt-4 inline-flex items-center rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                                Cancel
                            </button>
                        </div>
                    </div>

                    {{-- State: Connected --}}
                    <div id="connected-container" class="{{ (!$session || $session->status !== 'connected') ? 'hidden' : '' }}">
                        <div class="text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
                                <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </div>

                            <h3 class="mt-4 text-lg font-medium text-gray-900">WhatsApp Connected</h3>

                            <div class="mt-4 rounded-md bg-green-50 p-4">
                                <div class="flex flex-col items-center space-y-1">
                                    @if($session && $session->phone_number)
                                        <p class="text-sm font-medium text-green-800">{{ $session->phone_number }}</p>
                                    @endif
                                    @if($session && $session->display_name)
                                        <p class="text-sm text-green-700">{{ $session->display_name }}</p>
                                    @endif
                                    @if($session && $session->connected_at)
                                        <p class="text-xs text-green-600">Connected {{ $session->connected_at->diffForHumans() }}</p>
                                    @endif
                                </div>
                            </div>

                            <button id="disconnect-btn"
                                    class="mt-6 inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                                Disconnect
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const connectBtn = document.getElementById('connect-btn');
            const cancelBtn = document.getElementById('cancel-btn');
            const disconnectBtn = document.getElementById('disconnect-btn');
            const disconnectedContainer = document.getElementById('disconnected-container');
            const qrContainer = document.getElementById('qr-container');
            const connectedContainer = document.getElementById('connected-container');
            const qrImage = document.getElementById('qr-image');
            const qrLoading = document.getElementById('qr-loading');

            let pollingInterval = null;

            function showContainer(container) {
                [disconnectedContainer, qrContainer, connectedContainer].forEach(c => c.classList.add('hidden'));
                container.classList.remove('hidden');
            }

            connectBtn?.addEventListener('click', async function () {
                connectBtn.disabled = true;
                connectBtn.textContent = 'Starting...';

                try {
                    const response = await axios.post('/whatsapp/connect');

                    if (response.data.success) {
                        showContainer(qrContainer);
                        qrImage.classList.add('hidden');
                        qrLoading.classList.remove('hidden');
                        startQrPolling();
                    }
                } catch (error) {
                    connectBtn.disabled = false;
                    connectBtn.textContent = 'Connect WhatsApp';
                    alert('Failed to start WhatsApp session. Please try again.');
                }
            });

            cancelBtn?.addEventListener('click', function () {
                stopPolling();
                showContainer(disconnectedContainer);
                connectBtn.disabled = false;
                connectBtn.textContent = 'Connect WhatsApp';
            });

            disconnectBtn?.addEventListener('click', async function () {
                if (!confirm('Disconnect WhatsApp? You will need to scan the QR code again to reconnect.')) {
                    return;
                }

                disconnectBtn.disabled = true;
                disconnectBtn.textContent = 'Disconnecting...';

                try {
                    await axios.post('/whatsapp/disconnect');
                    window.location.reload();
                } catch (error) {
                    disconnectBtn.disabled = false;
                    disconnectBtn.textContent = 'Disconnect';
                    alert('Failed to disconnect. Please try again.');
                }
            });

            function startQrPolling() {
                pollQr();
                pollingInterval = setInterval(pollQr, 2500);
            }

            function stopPolling() {
                if (pollingInterval) {
                    clearInterval(pollingInterval);
                    pollingInterval = null;
                }
            }

            async function pollQr() {
                try {
                    const response = await axios.get('/whatsapp/qr-code');
                    const data = response.data;

                    if (data.status === 'connected') {
                        stopPolling();
                        window.location.reload();
                        return;
                    }

                    if (data.status === 'scan_qr' && data.qr) {
                        qrImage.src = data.qr;
                        qrImage.classList.remove('hidden');
                        qrLoading.classList.add('hidden');
                    }
                } catch (error) {
                    console.error('QR polling error:', error);
                }
            }

            // If page loaded in qr_pending state, start polling
            if (qrContainer && !qrContainer.classList.contains('hidden')) {
                startQrPolling();
            }
        });
    </script>
</x-app-layout>
