import { Head, Link } from '@inertiajs/react';
import {
    ArrowUpRight,
    BrainCircuit,
    FileUp,
    MessageCircleMore,
    Sparkles,
} from 'lucide-react';

import { AppSidebar } from '@/components/app-sidebar';
import { ThemeToggle } from '@/components/theme-toggle';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';

const actionCards = [
    {
        title: 'WhatsApp',
        description: 'Resume the live session, inspect QR status, and manage auto-reply contacts.',
        hrefKey: 'whatsapp',
        icon: MessageCircleMore,
        cta: 'Open WhatsApp',
        tone: 'border-emerald-200/70 bg-emerald-50/80 text-emerald-950 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-100',
        iconTone: 'bg-white/90 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200',
        buttonTone: 'bg-emerald-600 text-white hover:bg-emerald-500 dark:bg-emerald-500 dark:text-emerald-950 dark:hover:bg-emerald-400',
    },
    {
        title: 'Imports',
        description: 'Bring in exported history and keep the retrieval layer fresh for style matching.',
        hrefKey: 'imports',
        icon: FileUp,
        cta: 'Open imports',
        tone: 'border-sky-200/70 bg-sky-50/80 text-sky-950 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-100',
        iconTone: 'bg-white/90 text-sky-700 dark:bg-sky-500/15 dark:text-sky-200',
        buttonTone: 'bg-sky-700 text-white hover:bg-sky-600 dark:bg-sky-500 dark:text-sky-950 dark:hover:bg-sky-400',
    },
    {
        title: 'AI Settings',
        description: 'Review provider credentials, model selection, and the current generation stack.',
        hrefKey: 'ai',
        icon: BrainCircuit,
        cta: 'Open settings',
        tone: 'border-violet-200/70 bg-violet-50/80 text-violet-950 dark:border-violet-500/20 dark:bg-violet-500/10 dark:text-violet-100',
        iconTone: 'bg-white/90 text-violet-700 dark:bg-violet-500/15 dark:text-violet-200',
        buttonTone: 'bg-violet-700 text-white hover:bg-violet-600 dark:bg-violet-500 dark:text-violet-950 dark:hover:bg-violet-400',
    },
];

export default function Dashboard({ auth, urls }) {
    return (
        <>
            <Head title="Dashboard" />

            <SidebarProvider defaultOpen>
                <AppSidebar auth={auth} urls={urls} activePage="dashboard" />

                <SidebarInset className="bg-background">
                    <header className="sticky top-0 z-20 border-b border-border/70 bg-background/85 backdrop-blur">
                        <div className="flex flex-col gap-4 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                            <div className="flex items-start gap-3">
                                <SidebarTrigger className="mt-1" />
                                <Separator orientation="vertical" className="mt-1 hidden h-6 bg-border lg:block" />

                                <div>
                                    <div className="inline-flex items-center gap-2 rounded-full border border-primary/25 bg-primary/10 px-3 py-1 text-xs font-medium uppercase tracking-[0.22em] text-primary">
                                        <Sparkles className="size-3.5" />
                                        Shadcn dashboard
                                    </div>
                                    <h1 className="mt-3 text-2xl font-semibold tracking-tight text-foreground">
                                        Operator dashboard
                                    </h1>
                                    <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
                                        Quick access to WhatsApp, imports, and AI settings.
                                    </p>
                                </div>
                            </div>

                            <div className="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center">
                                {auth?.user ? (
                                    <div className="hidden rounded-2xl border border-border bg-card px-4 py-2 text-sm text-muted-foreground lg:block">
                                        {auth.user.name}
                                    </div>
                                ) : null}

                                <ThemeToggle />
                                <Button asChild variant="outline">
                                    <Link href={urls.ai}>Review provider stack</Link>
                                </Button>
                                <Button asChild className="bg-emerald-600 hover:bg-emerald-500">
                                    <Link href={urls.whatsapp}>Open WhatsApp tools</Link>
                                </Button>
                            </div>
                        </div>
                    </header>

                    <div className="flex-1 px-4 py-6 sm:px-6">
                        <div className="grid gap-6 xl:grid-cols-[1.6fr_1fr]">
                            <section className="overflow-hidden rounded-[28px] border border-border bg-card text-card-foreground shadow-sm">
                                <div className="bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.24),_transparent_32%),radial-gradient(circle_at_right,_rgba(56,189,248,0.18),_transparent_24%)] p-7 sm:p-8 dark:bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.22),_transparent_34%),radial-gradient(circle_at_right,_rgba(59,130,246,0.16),_transparent_26%)]">
                                    <div className="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                                        <div className="max-w-2xl">
                                            <div className="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/10 px-3 py-1 text-xs font-medium uppercase tracking-[0.2em] text-primary">
                                                <Sparkles className="size-3.5" />
                                                Control center
                                            </div>
                                            <h2 className="mt-5 text-3xl font-semibold tracking-tight sm:text-4xl">
                                                Manage the app from one clean workspace.
                                            </h2>
                                            <p className="mt-4 max-w-xl text-sm leading-6 text-muted-foreground sm:text-base">
                                                The new shell keeps the dashboard focused while we migrate the rest of
                                                the product into the same React and shadcn UI system.
                                            </p>
                                        </div>

                                        <Button asChild className="bg-primary text-primary-foreground hover:bg-primary/90">
                                            <Link href={urls.whatsapp}>Go to WhatsApp</Link>
                                        </Button>
                                    </div>
                                </div>
                            </section>

                            <section className="rounded-[28px] border border-border bg-card p-6 shadow-sm">
                                <p className="text-sm font-medium uppercase tracking-[0.22em] text-muted-foreground">
                                    Quick actions
                                </p>
                                <div className="mt-5 space-y-4">
                                    {actionCards.map((card) => {
                                        const Icon = card.icon;

                                        return (
                                            <div
                                                key={card.title}
                                                className={`rounded-2xl border p-4 ${card.tone}`}
                                            >
                                                <div className="flex items-start justify-between gap-4">
                                                    <div>
                                                        <div className={`flex size-11 items-center justify-center rounded-2xl shadow-sm ${card.iconTone}`}>
                                                            <Icon className="size-5" />
                                                        </div>
                                                        <h3 className="mt-4 text-base font-semibold">
                                                            {card.title}
                                                        </h3>
                                                        <p className="mt-1 text-sm leading-6 opacity-80">
                                                            {card.description}
                                                        </p>
                                                    </div>
                                                    <ArrowUpRight className="size-4 opacity-50" />
                                                </div>

                                                <Button asChild className={`mt-4 ${card.buttonTone}`}>
                                                    <Link href={urls[card.hrefKey]}>{card.cta}</Link>
                                                </Button>
                                            </div>
                                        );
                                    })}
                                </div>
                            </section>
                        </div>
                    </div>
                </SidebarInset>
            </SidebarProvider>
        </>
    );
}
