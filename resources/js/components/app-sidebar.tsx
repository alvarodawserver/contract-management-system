import { Link, usePage } from '@inertiajs/react';
import { FileText, History, LayoutGrid, Trash2 } from 'lucide-react';
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
import { dashboard } from '@/routes';
import { index as contractsIndex } from '@/routes/contracts';
import { index as trashIndex } from '@/routes/contracts/trash';
import { index as movementsIndex } from '@/routes/movements';
import type { NavItem } from '@/types';

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    const { simulation } = usePage().props;
    const currentUser = simulation.users.find(
        (user) => user.id === simulation.current,
    );
    // Department heads and admins also get the management-only views: the dashboard and
    // the cross-contract movement log. A delegated employee sees a contract's own history
    // from that contract's "View movements" button instead.
    const isManager =
        currentUser?.role === 'admin' ||
        currentUser?.role === 'department_head';

    const mainNavItems: NavItem[] = [
        {
            title: 'My contracts',
            href: contractsIndex(),
            icon: FileText,
        },
        // Everyone can reach the trash: an employee who deleted their own contract by
        // mistake can restore it themselves, without needing their department head.
        { title: 'Trash', href: trashIndex(), icon: Trash2 },
        ...(isManager
            ? [
                  { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
                  { title: 'Movements', href: movementsIndex(), icon: History },
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()}>
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
