import { Link } from '@inertiajs/react';
import { Fingerprint } from 'lucide-react';

import { ThemeToggle } from '@/components/theme-toggle';

export function AuthLayout({ title, description, children, footer }) {
    return (
        <div className="min-h-screen bg-background text-foreground">
            <div className="mx-auto flex min-h-screen max-w-6xl flex-col px-4 py-6 sm:px-6 lg:flex-row lg:items-center lg:gap-12 lg:px-8">
                <aside className="hidden lg:block lg:w-[32%] lg:pr-4">
                    <div>
                        <Link href="/" className="inline-flex items-center gap-3 text-foreground">
                            <div className="flex size-10 items-center justify-center rounded-2xl border border-border bg-card">
                                <Fingerprint className="size-4" />
                            </div>
                            <div>
                                <p className="text-sm font-semibold">Impersonator</p>
                                <p className="text-xs text-muted-foreground">AI-assisted WhatsApp replies</p>
                            </div>
                        </Link>

                        <div className="mt-10 max-w-sm">
                            <h1 className="text-3xl font-semibold tracking-tight text-foreground">
                                A small control panel for AI-assisted message replies.
                            </h1>
                            <p className="mt-4 text-sm leading-6 text-muted-foreground">
                                Connect WhatsApp, import message history, and tune the AI stack that powers suggested
                                replies.
                            </p>

                            <ul className="mt-8 space-y-3 text-sm text-muted-foreground">
                                <li>Connect and manage a WhatsApp session.</li>
                                <li>Import past conversations for retrieval and tone matching.</li>
                                <li>Choose providers and models for generated replies.</li>
                            </ul>
                        </div>
                    </div>
                </aside>

                <main className="flex flex-1 flex-col justify-center lg:max-w-md lg:py-8">
                    <div className="flex justify-end lg:mb-6">
                        <ThemeToggle />
                    </div>

                    <div className="mx-auto w-full max-w-md rounded-[32px] border border-border bg-card p-6 shadow-sm sm:p-8">
                        <div>
                            <p className="text-sm font-medium uppercase tracking-[0.22em] text-primary">Account</p>
                            <h2 className="mt-3 text-3xl font-semibold tracking-tight text-card-foreground">{title}</h2>
                            <p className="mt-2 text-sm leading-6 text-muted-foreground">{description}</p>
                        </div>

                        <div className="mt-8">{children}</div>

                        {footer ? <div className="mt-8 border-t border-border pt-6">{footer}</div> : null}
                    </div>
                </main>
            </div>
        </div>
    );
}
