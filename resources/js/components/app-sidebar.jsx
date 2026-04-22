import {
    Link,
    usePage,
} from '@inertiajs/react';
import {
    Bot,
    BrainCircuit,
    FileUp,
    LayoutDashboard,
    LogOut,
    MessageCircleMore,
} from 'lucide-react';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarRail,
} from '@/components/ui/sidebar';

const workspaceItems = [
    {
        title: 'Dashboard',
        hrefKey: 'dashboard',
        icon: LayoutDashboard,
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

export function AppSidebar({ activePage = 'dashboard' }) {
    const { auth = {}, urls = {} } = usePage().props;
    const initials = (auth?.user?.name ?? 'U')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');

    return (
        <Sidebar variant="inset" collapsible="icon">
            <SidebarHeader className="border-b border-sidebar-border/70 px-3 py-3">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            asChild
                            size="lg"
                            className="h-12 rounded-xl bg-sidebar-primary/10 px-3 data-[active=true]:bg-sidebar-primary/15 group-data-[collapsible=icon]:justify-center"
                            isActive
                        >
                            <Link href={urls.dashboard} prefetch="hover">
                                <div className="flex size-9 items-center justify-center rounded-xl bg-sidebar-primary text-sidebar-primary-foreground">
                                    <Bot className="size-4" />
                                </div>
                                <div className="grid flex-1 text-left leading-tight group-data-[collapsible=icon]:hidden">
                                    <span className="truncate text-sm font-semibold">Impersonator</span>
                                </div>
                            </Link>
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
                                const isActive = item.hrefKey === activePage;

                                return (
                                    <SidebarMenuItem key={item.title}>
                                        <SidebarMenuButton
                                            asChild
                                            tooltip={item.title}
                                            isActive={isActive}
                                        >
                                            <Link href={urls[item.hrefKey]} prefetch="hover">
                                                <Icon />
                                                <span>{item.title}</span>
                                            </Link>
                                        </SidebarMenuButton>
                                    </SidebarMenuItem>
                                );
                            })}
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>
            </SidebarContent>

            <SidebarFooter className="border-t border-sidebar-border/70 px-2 py-3">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            asChild
                            size="lg"
                            tooltip="Account"
                            className="h-auto min-h-12 rounded-xl px-2 group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:px-0"
                            isActive={activePage === 'profile'}
                        >
                            <Link href={urls.profile} prefetch="hover">
                                <div className="flex size-8 items-center justify-center rounded-xl bg-sidebar-primary/15 text-xs font-semibold text-sidebar-primary">
                                    {initials || 'U'}
                                </div>
                                <div className="grid flex-1 text-left leading-tight group-data-[collapsible=icon]:hidden">
                                    <span className="truncate text-sm font-medium text-sidebar-foreground">
                                        {auth?.user?.name ?? 'Authenticated user'}
                                    </span>
                                    <span className="truncate text-xs text-sidebar-foreground/70">
                                        {auth?.user?.email ?? 'Profile available'}
                                    </span>
                                </div>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <SidebarMenuItem className="group-data-[collapsible=icon]:hidden">
                        <SidebarMenuButton asChild tooltip="Profile">
                            <Link href={urls.profile} prefetch="hover">
                                <span>Profile</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    {auth?.logoutUrl ? (
                        <SidebarMenuItem className="group-data-[collapsible=icon]:hidden">
                            <SidebarMenuButton asChild tooltip="Log out">
                                <Link href={auth.logoutUrl} method="post" as="button">
                                    <LogOut />
                                    <span>Log out</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    ) : null}
                </SidebarMenu>
            </SidebarFooter>

            <SidebarRail />
        </Sidebar>
    );
}
