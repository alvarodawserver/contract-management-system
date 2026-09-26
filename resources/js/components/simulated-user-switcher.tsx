import { router, usePage } from '@inertiajs/react';
import {
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { store } from '@/routes/simulated-user';
import type { RoleSlug } from '@/types';

const ROLE_LABELS: Record<RoleSlug, string> = {
    admin: 'Admin',
    department_head: 'Department head',
    delegated_employee: 'Delegated employee',
};

/**
 * Demo-only stand-in for a real login: lets you act as any seeded user. Switching reloads
 * the whole page, since almost everything (role, department, visible contracts) depends on it.
 */
export function SimulatedUserSwitcher() {
    const { simulation } = usePage().props;

    return (
        <>
            <DropdownMenuLabel className="text-muted-foreground text-xs font-normal">
                Acting as (demo)
            </DropdownMenuLabel>

            <DropdownMenuRadioGroup
                value={String(simulation.current)}
                onValueChange={(userId) =>
                    router.post(
                        store().url,
                        { user_id: userId },
                        { preserveScroll: true },
                    )
                }
            >
                {simulation.users.map((user) => (
                    <DropdownMenuRadioItem
                        key={user.id}
                        value={String(user.id)}
                    >
                        <span className="flex flex-col">
                            <span>{user.name}</span>
                            <span className="text-muted-foreground text-xs">
                                {user.role ? ROLE_LABELS[user.role] : 'No role'}
                                {user.department ? ` · ${user.department}` : ''}
                            </span>
                        </span>
                    </DropdownMenuRadioItem>
                ))}
            </DropdownMenuRadioGroup>

            <DropdownMenuSeparator />
        </>
    );
}
