import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    FlaskConical,
    FolderGit2,
    LineChart,
    ShoppingCart,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { Auth, NavItem } from '@/types';

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth, tracking } = usePage<{
        auth: Auth;
        tracking: { mode: string };
    }>().props;
    const isAdmin = auth.user?.role === 'admin';

    const mainNavItems: NavItem[] = [
        ...(isAdmin
            ? [
                  {
                      title: 'Analytics',
                      href: '/admin',
                      icon: LineChart,
                  },
                  {
                      title: 'A/B Labs',
                      href: '/admin/labs',
                      icon: FlaskConical,
                  },
                  ...(tracking.mode === 'form'
                      ? [
                            {
                                title: 'Orders',
                                href: '/admin/orders',
                                icon: ShoppingCart,
                            },
                        ]
                      : []),
              ]
            : []),
    ];

    return (
        <Sidebar
            collapsible="icon"
            variant="sidebar"
            className="border-r border-blue-500/15 bg-sidebar/95 shadow-[12px_0_44px_-28px_rgba(29,78,216,0.55)] backdrop-blur-xl"
        >
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/admin" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
