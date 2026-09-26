export type Department = {
    id: number;
    name: string;
    code: string;
};

export type ContractStatus = 'pending' | 'formalized' | 'lapsed';

export type ContractMovement = {
    id: number;
    // Null once the contract has been permanently deleted; contract_reference survives it.
    contract_id: number | null;
    action: 'created' | 'updated' | 'deleted' | 'restored' | 'force_deleted';
    contract_reference: string;
    changes: Record<string, { old: unknown; new: unknown }> | null;
    created_at: string;
    user: { id: number; name: string } | null;
};

export type Contract = {
    id: number;
    reference: string;
    title: string;
    description: string | null;
    type: string | null;
    responsible: string | null;
    status: ContractStatus;
    expected_date: string | null;
    start_date: string | null;
    end_date: string | null;
    duration: { years: number; months: number; days: number } | null;
    expected_amount: string | null;
    amount: string | null;
    formalization_deadline: string | null;
    formalized_at: string | null;
    created_at: string;
    deleted_at: string | null;
    department?: Department;
    creator?: { id: number; name: string };
    can: {
        update: boolean;
        delete: boolean;
        restore: boolean;
        forceDelete: boolean;
    };
};

export type Paginated<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
};
