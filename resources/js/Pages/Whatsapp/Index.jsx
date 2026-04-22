import { Head, Link } from '@inertiajs/react';
import axios from 'axios';
import {
    CheckCircle2,
    LoaderCircle,
    MessageCircleMore,
    QrCode,
    RefreshCw,
    Smartphone,
    Unplug,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

import { AppShell } from '@/components/app-shell';
import { Button } from '@/components/ui/button';

function getInitialViewState(session) {
    if (!session || ['disconnected', 'failed'].includes(session.status)) {
        return 'disconnected';
    }

    if (session.status === 'qr_pending') {
        return 'qr_pending';
    }

    if (session.status === 'connected') {
        return 'connected';
    }

    return 'disconnected';
}

export default function WhatsappIndex({ auth, session, urls }) {
    const [viewState, setViewState] = useState(getInitialViewState(session));
    const [sessionData, setSessionData] = useState(session);
    const [qrCode, setQrCode] = useState(null);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isDisconnecting, setIsDisconnecting] = useState(false);
    const [errorMessage, setErrorMessage] = useState(
        session?.status === 'failed' ? 'Connection failed. Please try again.' : null,
    );

    const isDisconnected = viewState === 'disconnected';
    const isQrPending = viewState === 'qr_pending';
    const isConnected = viewState === 'connected';

    const connectedDetails = useMemo(() => ({
        phoneNumber: sessionData?.phone_number,
        displayName: sessionData?.display_name,
        connectedAt: sessionData?.connected_at,
    }), [sessionData]);

    useEffect(() => {
        if (!isQrPending) {
            return undefined;
        }

        let active = true;
        let intervalId = null;

        const pollQr = async () => {
            try {
                const response = await axios.get(urls.qrCode);
                const data = response.data;

                if (!active) {
                    return;
                }

                if (data.status === 'connected') {
                    const statusResponse = await axios.get(urls.status);

                    if (!active) {
                        return;
                    }

                    setSessionData((current) => ({
                        ...(current ?? {}),
                        ...statusResponse.data,
                    }));
                    setViewState('connected');
                    setQrCode(null);
                    setErrorMessage(null);

                    return;
                }

                if (data.status === 'scan_qr' && data.qr) {
                    setQrCode(data.qr);
                    setErrorMessage(null);

                    return;
                }

                if (data.status === 'FAILED') {
                    setViewState('disconnected');
                    setQrCode(null);
                    setErrorMessage('Connection failed. Please try again.');
                }
            } catch (error) {
                if (active) {
                    setErrorMessage('Unable to refresh the QR code right now. Please try again.');
                }
            }
        };

        pollQr();
        intervalId = window.setInterval(pollQr, 2500);

        return () => {
            active = false;
            if (intervalId) {
                window.clearInterval(intervalId);
            }
        };
    }, [isQrPending, urls.qrCode, urls.status]);

    async function handleConnect() {
        setIsSubmitting(true);
        setErrorMessage(null);
        setQrCode(null);

        try {
            const response = await axios.post(urls.connect);

            if (response.data.success) {
                setViewState('qr_pending');
                setSessionData((current) => ({
                    ...current,
                    status: 'qr_pending',
                }));
            }
        } catch (error) {
            setErrorMessage('Failed to start WhatsApp session. Please try again.');
        } finally {
            setIsSubmitting(false);
        }
    }

    function handleCancelQr() {
        setViewState('disconnected');
        setQrCode(null);
    }

    async function handleDisconnect() {
        if (!window.confirm('Disconnect WhatsApp? You will need to scan the QR code again to reconnect.')) {
            return;
        }

        setIsDisconnecting(true);
        setErrorMessage(null);

        try {
            await axios.post(urls.disconnect);

            setSessionData({
                status: 'disconnected',
                phone_number: null,
                display_name: null,
                connected_at: null,
            });
            setViewState('disconnected');
            setQrCode(null);
        } catch (error) {
            setErrorMessage('Failed to disconnect. Please try again.');
        } finally {
            setIsDisconnecting(false);
        }
    }

    return (
        <>
            <Head title="WhatsApp" />
            <div className="grid gap-6 xl:grid-cols-[1.5fr_0.9fr]">
                            <section className="overflow-hidden rounded-[28px] border border-border bg-card shadow-sm">
                                <div className="border-b border-border bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.18),_transparent_30%),radial-gradient(circle_at_right,_rgba(56,189,248,0.12),_transparent_24%)] p-7 sm:p-8">
                                    <div className="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                                        <div className="max-w-2xl">
                                            <div className="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/10 px-3 py-1 text-xs font-medium uppercase tracking-[0.2em] text-primary">
                                                <Smartphone className="size-3.5" />
                                                Session status
                                            </div>
                                            <h2 className="mt-5 text-3xl font-semibold tracking-tight text-card-foreground sm:text-4xl">
                                                {isConnected && 'WhatsApp is connected and ready.'}
                                                {isQrPending && 'Scan the QR code from your phone.'}
                                                {isDisconnected && 'Start a new WhatsApp connection.'}
                                            </h2>
                                            <p className="mt-4 max-w-xl text-sm leading-6 text-muted-foreground sm:text-base">
                                                This page mirrors the existing WAHA flow while moving the UI into the
                                                new React and shadcn shell.
                                            </p>
                                        </div>

                                        <div className="rounded-3xl border border-border bg-background/80 p-4 text-sm text-muted-foreground">
                                            <div className="flex items-center gap-2">
                                                <div
                                                    className={`size-2.5 rounded-full ${
                                                        isConnected
                                                            ? 'bg-emerald-500'
                                                            : isQrPending
                                                              ? 'bg-amber-500'
                                                              : 'bg-slate-400'
                                                    }`}
                                                />
                                                {isConnected && 'Connected'}
                                                {isQrPending && 'Waiting for scan'}
                                                {isDisconnected && 'Not connected'}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div className="p-7 sm:p-8">
                                    {errorMessage ? (
                                        <div className="mb-6 rounded-2xl border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                                            {errorMessage}
                                        </div>
                                    ) : null}

                                    {isDisconnected ? (
                                        <div className="flex flex-col items-center text-center">
                                            <div className="flex size-20 items-center justify-center rounded-3xl bg-primary/10 text-primary">
                                                <MessageCircleMore className="size-10" />
                                            </div>
                                            <h3 className="mt-6 text-2xl font-semibold text-card-foreground">
                                                Connect WhatsApp
                                            </h3>
                                            <p className="mt-3 max-w-lg text-sm leading-6 text-muted-foreground">
                                                Link your WhatsApp account by scanning a QR code, just like WhatsApp
                                                Web. Once connected, you can jump directly into auto-reply setup.
                                            </p>

                                            <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                                                <Button
                                                    type="button"
                                                    className="bg-emerald-600 text-white hover:bg-emerald-500"
                                                    onClick={handleConnect}
                                                    disabled={isSubmitting}
                                                >
                                                    {isSubmitting ? (
                                                        <>
                                                            <LoaderCircle className="size-4 animate-spin" />
                                                            Starting...
                                                        </>
                                                    ) : (
                                                        'Connect WhatsApp'
                                                    )}
                                                </Button>
                                                <Button asChild variant="outline">
                                                    <Link href={urls.autoReply}>Open auto-reply settings</Link>
                                                </Button>
                                            </div>
                                        </div>
                                    ) : null}

                                    {isQrPending ? (
                                        <div className="flex flex-col items-center text-center">
                                            <div className="flex size-20 items-center justify-center rounded-3xl bg-amber-500/10 text-amber-500">
                                                <QrCode className="size-10" />
                                            </div>
                                            <h3 className="mt-6 text-2xl font-semibold text-card-foreground">
                                                Scan QR code
                                            </h3>
                                            <p className="mt-3 max-w-lg text-sm leading-6 text-muted-foreground">
                                                Open WhatsApp on your phone, go to Settings, Linked Devices, and scan
                                                the code below. It refreshes automatically while this page stays open.
                                            </p>

                                            <div className="mt-8 flex min-h-72 w-full max-w-sm items-center justify-center rounded-[28px] border border-dashed border-border bg-background p-5">
                                                {qrCode ? (
                                                    <img
                                                        src={qrCode}
                                                        alt="WhatsApp QR Code"
                                                        className="size-64 rounded-2xl object-contain"
                                                    />
                                                ) : (
                                                    <div className="flex flex-col items-center gap-3 text-muted-foreground">
                                                        <LoaderCircle className="size-8 animate-spin" />
                                                        <p className="text-sm">Loading QR code...</p>
                                                    </div>
                                                )}
                                            </div>

                                            <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                                                <Button type="button" variant="outline" onClick={handleCancelQr}>
                                                    Cancel
                                                </Button>
                                                <Button asChild className="bg-slate-900 text-white hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-950 dark:hover:bg-white">
                                                    <Link href={urls.autoReply}>Auto-reply settings</Link>
                                                </Button>
                                            </div>
                                        </div>
                                    ) : null}

                                    {isConnected ? (
                                        <div className="flex flex-col items-center text-center">
                                            <div className="flex size-20 items-center justify-center rounded-3xl bg-emerald-500/10 text-emerald-500">
                                                <CheckCircle2 className="size-10" />
                                            </div>
                                            <h3 className="mt-6 text-2xl font-semibold text-card-foreground">
                                                WhatsApp connected
                                            </h3>
                                            <p className="mt-3 max-w-lg text-sm leading-6 text-muted-foreground">
                                                Your WAHA session is active. You can manage auto-replies now or
                                                disconnect and start a fresh link flow.
                                            </p>

                                            <div className="mt-8 w-full max-w-md rounded-[28px] border border-emerald-500/20 bg-emerald-500/10 p-5">
                                                <div className="space-y-2 text-left">
                                                    {connectedDetails.phoneNumber ? (
                                                        <p className="text-sm font-medium text-emerald-950 dark:text-emerald-100">
                                                            {connectedDetails.phoneNumber}
                                                        </p>
                                                    ) : null}
                                                    {connectedDetails.displayName ? (
                                                        <p className="text-sm text-emerald-800 dark:text-emerald-200">
                                                            {connectedDetails.displayName}
                                                        </p>
                                                    ) : null}
                                                    {connectedDetails.connectedAt ? (
                                                        <p className="text-xs text-emerald-700/80 dark:text-emerald-200/80">
                                                            Connected {connectedDetails.connectedAt}
                                                        </p>
                                                    ) : null}
                                                </div>
                                            </div>

                                            <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                                                <Button asChild className="bg-emerald-600 text-white hover:bg-emerald-500">
                                                    <Link href={urls.autoReply}>Auto-reply settings</Link>
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="destructive"
                                                    onClick={handleDisconnect}
                                                    disabled={isDisconnecting}
                                                >
                                                    {isDisconnecting ? (
                                                        <>
                                                            <RefreshCw className="size-4 animate-spin" />
                                                            Disconnecting...
                                                        </>
                                                    ) : (
                                                        <>
                                                            <Unplug className="size-4" />
                                                            Disconnect
                                                        </>
                                                    )}
                                                </Button>
                                            </div>
                                        </div>
                                    ) : null}
                                </div>
                            </section>

                            <section className="rounded-[28px] border border-border bg-card p-6 shadow-sm">
                                <p className="text-sm font-medium uppercase tracking-[0.22em] text-muted-foreground">
                                    Connection flow
                                </p>

                                <div className="mt-5 space-y-4">
                                    <div className="rounded-2xl border border-border bg-background p-4">
                                        <p className="text-sm font-semibold text-card-foreground">1. Start session</p>
                                        <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                            The app creates or resumes the WAHA session and prepares the QR handshake.
                                        </p>
                                    </div>

                                    <div className="rounded-2xl border border-border bg-background p-4">
                                        <p className="text-sm font-semibold text-card-foreground">2. Scan QR code</p>
                                        <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                            Your phone links the session just like WhatsApp Web, and the page keeps polling until the session flips live.
                                        </p>
                                    </div>

                                    <div className="rounded-2xl border border-border bg-background p-4">
                                        <p className="text-sm font-semibold text-card-foreground">3. Configure auto-replies</p>
                                        <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                            Once connected, jump into contact rules, traces, and reply behavior from the same operator shell.
                                        </p>
                                    </div>
                                </div>
                            </section>
            </div>
        </>
    );
}

WhatsappIndex.layout = (page) => (
    <AppShell
        activePage="whatsapp"
        badge="WhatsApp connection"
        badgeIcon={MessageCircleMore}
        title="Connect and manage your WAHA session"
        description="Scan a QR code to link WhatsApp, review connection status, and jump straight into auto-reply controls once the session is live."
    >
        {page}
    </AppShell>
);
