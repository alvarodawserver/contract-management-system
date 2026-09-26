import { useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { ContractStatusBadge } from '@/components/contract-status-badge';
import { DepartmentFilter } from '@/components/department-filter';
import Heading from '@/components/heading';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    forceDestroy,
    index as contractsIndex,
    restore,
} from '@/routes/contracts';
import { index as trashIndex } from '@/routes/contracts/trash';
import type { Contract, Department, Paginated } from '@/types';

type Filters = {
    department_id: number | null;
    search: string | null;
};

type Props = {
    contracts: Paginated<Contract>;
    filters: Filters;
    departments: Department[];
};

export default function ContractsTrash({
    contracts,
    filters,
    departments,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const isFirstRender = useRef(true);

    // Same debounce as "My contracts": wait until the user stops typing before searching.
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }

        const timeout = setTimeout(() => {
            navigate({ search: search === '' ? null : search });
        }, 400);

        return () => clearTimeout(timeout);
    }, [search]);

    function navigate(changes: Partial<Filters>) {
        const next = { ...filters, ...changes };

        router.get(
            trashIndex.url({
                query: {
                    department_id: next.department_id ?? undefined,
                    search: next.search ?? undefined,
                },
            }),
            {},
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    // A hard reload on success rather than trusting Inertia's in-place update: restoring or
    // permanently deleting a contract is rare and deliberate, so it's worth the full page
    // load to be certain the list (and wherever the redirect lands) shows the real state.
    function handleRestore(contract: Contract) {
        router.patch(
            restore(contract).url,
            {},
            {
                preserveScroll: true,
                onSuccess: () => window.location.reload(),
            },
        );
    }

    function handleForceDestroy(contract: Contract) {
        router.delete(forceDestroy(contract).url, {
            preserveScroll: true,
            onSuccess: () => window.location.reload(),
        });
    }

    return (
        <>
            <Head title="Trash" />

            <div className="flex flex-col gap-4 p-4">
                <Heading
                    title="Trash"
                    description={`${contracts.meta.total} deleted contract${contracts.meta.total === 1 ? '' : 's'}`}
                />

                <div className="flex flex-wrap items-end gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="search">Search</Label>

                        <Input
                            id="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Title or reference"
                            className="w-64"
                        />
                    </div>

                    <DepartmentFilter
                        departments={departments}
                        value={filters.department_id}
                        onChange={(department_id) =>
                            navigate({ department_id })
                        }
                    />
                </div>

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-muted-foreground text-left">
                            <tr>
                                <th className="px-4 py-2 font-medium">
                                    Reference
                                </th>
                                <th className="px-4 py-2 font-medium">Title</th>
                                <th className="px-4 py-2 font-medium">
                                    Department
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Status
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Deleted at
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            {contracts.data.map((contract) => (
                                <tr key={contract.id} className="border-t">
                                    {/* A trashed contract can't be opened, so the reference is
                                        plain text here, not a link like in "My contracts". */}
                                    <td className="px-4 py-2 font-medium">
                                        {contract.reference}
                                    </td>
                                    <td className="px-4 py-2">
                                        {contract.title}
                                    </td>
                                    <td className="px-4 py-2">
                                        {contract.department?.name}
                                    </td>
                                    <td className="px-4 py-2">
                                        <ContractStatusBadge
                                            status={contract.status}
                                        />
                                    </td>
                                    <td className="px-4 py-2">
                                        {contract.deleted_at
                                            ? new Date(
                                                  contract.deleted_at,
                                              ).toLocaleDateString()
                                            : '—'}
                                    </td>
                                    <td className="px-4 py-2">
                                        <div className="flex gap-2">
                                            {contract.can.restore && (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        handleRestore(contract)
                                                    }
                                                >
                                                    Restore
                                                </Button>
                                            )}

                                            {contract.can.forceDelete && (
                                                <Dialog>
                                                    <DialogTrigger asChild>
                                                        <Button
                                                            type="button"
                                                            variant="destructive"
                                                            size="sm"
                                                        >
                                                            Delete permanently
                                                        </Button>
                                                    </DialogTrigger>

                                                    <DialogContent>
                                                        <DialogTitle>
                                                            Delete{' '}
                                                            {contract.reference}{' '}
                                                            permanently?
                                                        </DialogTitle>
                                                        <DialogDescription>
                                                            This cannot be
                                                            undone. The contract
                                                            will be removed for
                                                            good; its movement
                                                            history will still
                                                            record what happened
                                                            to it.
                                                        </DialogDescription>

                                                        <DialogFooter className="gap-2">
                                                            <DialogClose
                                                                asChild
                                                            >
                                                                <Button variant="secondary">
                                                                    Cancel
                                                                </Button>
                                                            </DialogClose>

                                                            <Button
                                                                type="button"
                                                                variant="destructive"
                                                                onClick={() =>
                                                                    handleForceDestroy(
                                                                        contract,
                                                                    )
                                                                }
                                                            >
                                                                Delete
                                                                permanently
                                                            </Button>
                                                        </DialogFooter>
                                                    </DialogContent>
                                                </Dialog>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}

                            {contracts.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="text-muted-foreground px-4 py-8 text-center"
                                    >
                                        The trash is empty.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination meta={contracts.meta} links={contracts.links} />
            </div>
        </>
    );
}

ContractsTrash.layout = {
    breadcrumbs: [
        { title: 'My contracts', href: contractsIndex() },
        { title: 'Trash', href: trashIndex() },
    ],
};
