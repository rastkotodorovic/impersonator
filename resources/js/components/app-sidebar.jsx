import {
    Bot,
    BrainCircuit,
    FileUp,
    LayoutDashboard,
    MessageCircleMore,
    UserRound,
} from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarRail,
} from '@/components/ui/sidebar';

const workspaceItems = [
    {
        title: 'Dashboard',
        hrefKey: 'dashboard',
        icon: LayoutDashboard,
        badge: 'Now',
    },
    {
        title: 'WhatsApp',
        hrefKey: 'whatsapp',
        icon: MessageCircleMore,
    },
    {
        title: 'Imports',
        hrefKey: 'imports',
        icon: FileUp,
    },
    {
        title: 'AI Settings',
        hrefKey: 'ai',
        icon: BrainCircuit,
    },
];

export function AppSidebar({ auth, urls }) {
    return (
        <Sidebar variant="inset" collapsible="icon">
            <SidebarHeader className="border-b border-sidebar-border/70 px-3 py-3">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            asChild
                            size="lg"
                            className="h-12 rounded-xl bg-sidebar-primary/10 px-3 data-[active=true]:bg-sidebar-primary/15"
                            isActive
                        >
                            <a href={urls.dashboard}>
                                <div className="flex size-9 items-center justify-center rounded-xl bg-sidebar-primary text-sidebar-primary-foreground">
                                    <Bot className="size-4" />
                                </div>
                                <div className="grid flex-1 text-left leading-tight">
                                    <span className="truncate text-sm font-semibold">Impersonator</span>
                                    <span className="truncate text-xs text-sidebar-foreground/70">
                                        Shadcn operator console
                                    </span>
                                </div>
                            </a>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="px-2 py-3">
                <SidebarGroup>
                    <SidebarGroupLabel>Workspace</SidebarGroupLabel>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            {workspaceItems.map((item) => {
                                const Icon = item.icon;
                                const isActive = item.hrefKey === 'dashboard';

                                return (
                                    <SidebarMenuItem key={item.title}>
                                        <SidebarMenuButton
                                            asChild
                                            tooltip={item.title}
                                            isActive={isActive}
                                        >
                                            <a href={urls[item.hrefKey]}>
                                                <Icon />
                                                <span>{item.title}</span>
                                            </a>
                                        </SidebarMenuButton>
                                        {item.badge ? <SidebarMenuBadge>{item.badge}</SidebarMenuBadge> : null}
                                    </SidebarMenuItem>
                                );
                            })}
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>
            </SidebarContent>

            <SidebarFooter className="border-t border-sidebar-border/70 p-3">
                <div className="rounded-2xl border border-sidebar-border bg-sidebar-accent/50 p-3">
                    <div className="flex items-center gap-3">
                        <div className="flex size-10 items-center justify-center rounded-xl bg-sidebar-primary/15 text-sidebar-primary">
                            <UserRound className="size-5" />
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-medium text-sidebar-foreground">
                                {auth?.user?.name ?? 'Authenticated user'}
                            </p>
                            <p className="truncate text-xs text-sidebar-foreground/70">
                                {auth?.user?.email ?? 'Profile available'}
                            </p>
                        </div>
                    </div>

                    <Button asChild variant="outline" className="mt-3 w-full justify-start border-sidebar-border bg-sidebar">
                        <a href={urls.profile}>Open profile</a>
                    </Button>
                </div>
            </SidebarFooter>

            <SidebarRail />
        </Sidebar>
    );
}
