export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export type RoleSlug = 'admin' | 'department_head' | 'delegated_employee';

export type SimulatedUser = {
    id: number;
    name: string;
    role: RoleSlug | null;
    role_label: string | null;
    department: string | null;
};

/**
 * Who the app is currently acting as, and who the demo selector can switch to.
 * Shared with every page by HandleInertiaRequests.
 */
export type Simulation = {
    current: number | null;
    users: SimulatedUser[];
};
