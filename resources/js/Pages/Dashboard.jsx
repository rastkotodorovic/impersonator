import { Head } from '@inertiajs/react';
import { Bot, Import, MessageCircleMore, Settings2 } from 'lucide-react';

import { Button } from '@/components/ui/button';

const cards = [
    {
        key: 'whatsapp',
        title: 'WhatsApp',
        description: 'Connect a WAHA-backed WhatsApp session and manage auto-reply contacts.',
        hrefKey: 'whatsapp',
        icon: MessageCircleMore,
        className: 'bg-emerald-600 hover:bg-emerald-500',
        action: 'Open WhatsApp',
    },
    {
        key: 'imports',
        title: 'Message Import',
        description: 'Import Facebook Messenger, Instagram, or WhatsApp exports and rebuild retrieval data.',
        hrefKey: 'imports',
        icon: Import,
        className: 'bg-sky-700 hover:bg-sky-600',
        action: 'Open Imports',
    },
    {
        key: 'ai',
        title: 'AI Settings',
        description: 'Configure providers, models, and credentials for chat replies and embeddings.',
        hrefKey: 'ai',
        icon: Bot,
        className: 'bg-slate-900 hover:bg-slate-800',
        action: 'Open Settings',
    },
];

export default function Dashboard({ auth, urls }) {
    return (
        <>
            <Head title="Dashboard" />

            <div className="min-h-screen bg-slate-100">
                <header className="border-b bg-white/95 backdrop-blur">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
                        <div>
                            <p className="text-sm font-medium uppercase tracking-[0.24em] text-emerald-700">
                                Impersonator
                            </p>
                            <h1 className="mt-1 text-2xl font-semibold text-slate-900">Dashboard</h1>
                            <p className="mt-1 text-sm text-slate-500">
                                React, Inertia, and shadcn/ui are now wired into the app.
                            </p>
                        </div>

                        <div className="flex items-center gap-3">
                            {auth?.user ? (
                                <div className="hidden rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-600 sm:block">
                                    {auth.user.name}
                                </div>
                            ) : null}

                            <Button asChild variant="outline">
                                <a href={urls.profile}>Profile</a>
                            </Button>
                        </div>
                    </div>
                </header>

                <main className="mx-auto max-w-7xl px-6 py-12">
                    <section className="rounded-3xl border border-emerald-200 bg-gradient-to-r from-emerald-600 via-emerald-500 to-lime-500 p-8 text-white shadow-xl shadow-emerald-200">
                        <div className="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                            <div className="max-w-2xl">
                                <div className="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-medium uppercase tracking-[0.2em] text-emerald-50">
                                    <Settings2 className="h-3.5 w-3.5" />
                                    Frontend Foundation
                                </div>
                                <h2 className="mt-4 text-3xl font-semibold tracking-tight">
                                    The app can now support a gradual React migration.
                                </h2>
                                <p className="mt-3 text-sm leading-6 text-emerald-50/90 sm:text-base">
                                    Existing Blade pages still work, while new pages can be built with Inertia and shared
                                    shadcn/ui components.
                                </p>
                            </div>

                            <Button asChild variant="secondary" className="w-full sm:w-auto">
                                <a href={urls.whatsapp}>Continue to WhatsApp</a>
                            </Button>
                        </div>
                    </section>

                    <section className="mt-8 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        {cards.map((card) => {
                            const Icon = card.icon;

                            return (
                                <article
                                    key={card.key}
                                    className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition-transform duration-200 hover:-translate-y-1"
                                >
                                    <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                                        <Icon className="h-6 w-6" />
                                    </div>
                                    <h3 className="mt-5 text-lg font-semibold text-slate-900">{card.title}</h3>
                                    <p className="mt-2 text-sm leading-6 text-slate-500">{card.description}</p>

                                    <Button asChild className={`mt-6 ${card.className}`}>
                                        <a href={urls[card.hrefKey]}>{card.action}</a>
                                    </Button>
                                </article>
                            );
                        })}
                    </section>
                </main>
            </div>
        </>
    );
}
