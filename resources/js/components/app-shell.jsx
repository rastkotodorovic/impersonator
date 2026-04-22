import { ThemeToggle } from '@/components/theme-toggle';
import { Separator } from '@/components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';

import { AppSidebar } from './app-sidebar';

export function AppShell({
    activePage = 'dashboard',
    badge,
    badgeIcon: BadgeIcon,
    title,
    description,
    children,
}) {
    return (
        <SidebarProvider defaultOpen>
            <AppSidebar activePage={activePage} />

            <SidebarInset className="bg-background">
                <header className="sticky top-0 z-20 border-b border-border/70 bg-background/85 backdrop-blur">
                    <div className="flex flex-col gap-4 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                        <div className="flex items-start gap-3">
                            <SidebarTrigger className="mt-1" />
                            <Separator orientation="vertical" className="mt-1 hidden h-6 bg-border lg:block" />

                            <div>
                                {badge ? (
                                    <div className="inline-flex items-center gap-2 rounded-full border border-primary/25 bg-primary/10 px-3 py-1 text-xs font-medium uppercase tracking-[0.22em] text-primary">
                                        {BadgeIcon ? <BadgeIcon className="size-3.5" /> : null}
                                        {badge}
                                    </div>
                                ) : null}
                                <h1 className="mt-3 text-2xl font-semibold tracking-tight text-foreground">
                                    {title}
                                </h1>
                                {description ? (
                                    <p className="mt-1 max-w-3xl text-sm text-muted-foreground">
                                        {description}
                                    </p>
                                ) : null}
                            </div>
                        </div>

                        <div className="flex items-center">
                            <ThemeToggle />
                        </div>
                    </div>
                </header>

                <div className="flex-1 px-4 py-6 sm:px-6">{children}</div>
            </SidebarInset>
        </SidebarProvider>
    );
}
