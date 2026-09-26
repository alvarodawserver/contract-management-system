import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { show } from '@/routes/contracts';
import type { ContractMovement, Paginated } from '@/types';

const ACTION_LABELS: Record<ContractMovement['action'], string> = {
    created: 'Created',
    updated: 'Updated',
    deleted: 'Deleted',
    restored: 'Restored',
    force_deleted: 'Permanently deleted',
};

const ACTION_VARIANTS: Record<
    ContractMovement['action'],
    'secondary' | 'default' | 'destructive'
> = {
    created: 'default',
    updated: 'secondary',
    deleted: 'destructive',
    restored: 'default',
    force_deleted: 'destructive',
};

type Props = {
    movements: Paginated<ContractMovement>;
    /** Hidden on a single contract's own movements page: every row there is already about it. */
    showContract?: boolean;
};

function isPrimitive(value: unknown): value is string | number | boolean {
    return (
        typeof value === 'string' ||
        typeof value === 'number' ||
        typeof value === 'boolean'
    );
}

/**
 * A changed value is untyped JSON (`unknown`): usually a string, number or null, but never
 * guaranteed. Stringifying an object with `String()` would silently print "[object Object]".
 */
function formatValue(value: unknown): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return isPrimitive(value) ? String(value) : JSON.stringify(value);
}

export function ContractMovementsTable({
    movements,
    showContract = true,
}: Props) {
    const columnCount = showContract ? 5 : 4;

    return (
        <div className="overflow-hidden rounded-xl border">
            <table className="w-full text-sm">
                <thead className="bg-muted/50 text-muted-foreground text-left">
                    <tr>
                        {showContract && (
                            <th className="px-4 py-2 font-medium">Contract</th>
                        )}
                        <th className="px-4 py-2 font-medium">Action</th>
                        <th className="px-4 py-2 font-medium">By</th>
                        <th className="px-4 py-2 font-medium">When</th>
                        <th className="px-4 py-2 font-medium">Changes</th>
                    </tr>
                </thead>

                <tbody>
                    {movements.data.map((movement) => (
                        <tr key={movement.id} className="border-t align-top">
                            {showContract && (
                                <td className="px-4 py-2 font-medium">
                                    {movement.contract_id ? (
                                        <Link
                                            href={show(movement.contract_id)}
                                            className="hover:underline"
                                        >
                                            {movement.contract_reference}
                                        </Link>
                                    ) : (
                                        movement.contract_reference
                                    )}
                                </td>
                            )}

                            <td className="px-4 py-2">
                                <Badge
                                    variant={ACTION_VARIANTS[movement.action]}
                                >
                                    {ACTION_LABELS[movement.action]}
                                </Badge>
                            </td>

                            <td className="px-4 py-2">
                                {movement.user?.name ?? 'System'}
                            </td>

                            <td className="px-4 py-2">
                                {new Date(movement.created_at).toLocaleString()}
                            </td>

                            <td className="px-4 py-2">
                                {movement.changes ? (
                                    <details>
                                        <summary className="cursor-pointer select-none">
                                            View changes
                                        </summary>

                                        <ul className="text-muted-foreground mt-1 space-y-0.5">
                                            {Object.entries(
                                                movement.changes,
                                            ).map(([field, change]) => (
                                                <li key={field}>
                                                    <span className="font-medium">
                                                        {field}
                                                    </span>
                                                    : {formatValue(change.old)}{' '}
                                                    → {formatValue(change.new)}
                                                </li>
                                            ))}
                                        </ul>
                                    </details>
                                ) : (
                                    '—'
                                )}
                            </td>
                        </tr>
                    ))}

                    {movements.data.length === 0 && (
                        <tr>
                            <td
                                colSpan={columnCount}
                                className="text-muted-foreground px-4 py-8 text-center"
                            >
                                No movements yet.
                            </td>
                        </tr>
                    )}
                </tbody>
            </table>
        </div>
    );
}
