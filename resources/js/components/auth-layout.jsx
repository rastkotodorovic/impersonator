import { Link } from '@inertiajs/react';
import { Fingerprint, ShieldCheck } from 'lucide-react';

import { ThemeToggle } from '@/components/theme-toggle';

export function AuthLayout({ title, description, children, footer }) {
    return (
        <div className="min-h-screen bg-background text-foreground">
            <div className="mx-auto flex min-h-screen max-w-7xl flex-col px-4 py-6 sm:px-6 lg:flex-row lg:items-stretch lg:gap-6 lg:px-8">
                <aside className="relative hidden overflow-hidden rounded-[32px] border border-border bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.18),_transparent_30%),radial-gradient(circle_at_right,_rgba(59,130,246,0.16),_transparent_24%)] p-8 shadow-sm lg:flex lg:w-[40%] lg:flex-col lg:justify-between">
                    <div>
                        <Link href="/" className="inline-flex items-center gap-3">
                            <div className="flex size-11 items-center justify-center rounded-2xl bg-primary text-primary-foreground">
                                <Fingerprint className="size-5" />
                            </div>
                            <div>
                                <p className="text-sm font-semibold">Impersonator</p>
                                <p className="text-xs text-muted-foreground">AI-assisted WhatsApp replies</p>
                            </div>
                        </Link>

                        <div className="mt-14 max-w-md">
                            <div className="inline-flex items-center gap-2 rounded-full border border-primary/25 bg-primary/10 px-3 py-1 text-xs font-medium uppercase tracking-[0.22em] text-primary">
                                <ShieldCheck className="size-3.5" />
                                Account flow
                            </div>
                            <h1 className="mt-5 text-4xl font-semibold tracking-tight text-foreground">
                                One consistent shell for access, identity, and recovery.
                            </h1>
                            <p className="mt-4 text-sm leading-6 text-muted-foreground">
                                The same design system now covers login, registration, recovery, verification, and the
                                authenticated account pages.
                            </p>
                        </div>
                    </div>

                    <div className="rounded-3xl border border-border bg-background/70 p-5 backdrop-blur">
                        <p className="text-sm font-medium text-foreground">What this unlocks</p>
                        <p className="mt-2 text-sm leading-6 text-muted-foreground">
                            A cleaner onboarding path now that the main operator pages and account flow share the same
                            React and shadcn UI foundation.
                        </p>
                    </div>
                </aside>

                <main className="flex flex-1 flex-col justify-center lg:py-8">
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
