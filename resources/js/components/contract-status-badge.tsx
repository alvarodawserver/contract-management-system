import { Badge } from '@/components/ui/badge';
import type { ContractStatus } from '@/types';

const STATUS_LABELS: Record<ContractStatus, string> = {
    pending: 'Pending',
    formalized: 'Formalized',
    lapsed: 'Lapsed',
};

const STATUS_VARIANTS: Record<
    ContractStatus,
    'secondary' | 'default' | 'destructive'
> = {
    pending: 'secondary',
    formalized: 'default',
    lapsed: 'destructive',
};

export function ContractStatusBadge({ status }: { status: ContractStatus }) {
    return (
        <Badge variant={STATUS_VARIANTS[status]}>{STATUS_LABELS[status]}</Badge>
    );
}
