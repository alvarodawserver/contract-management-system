import { useEffect, useRef, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ContractActions } from '@/components/contract-actions';
import { ContractStatusBadge } from '@/components/contract-status-badge';
import { DepartmentFilter } from '@/components/department-filter';
import Heading from '@/components/heading';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { create, index, show } from '@/routes/contracts';
import type { Contract, ContractStatus, Department, Paginated } from '@/types';

type Filters = {
    department_id: number | null;
    status: ContractStatus | null;
    search: string | null;
};

type Props = {
    contracts: Paginated<Contract>;
    filters: Filters;
    departments: Department[];
};

export default function ContractsIndex({
    contracts,
    filters,
    departments,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    // Skip the very first run: on mount, `search` already matches `filters.search`
    // (that's what the server just rendered), so there is nothing new to ask for.
    const isFirstRender = useRef(true);

    // Runs again every time `search` changes, i.e. on every keystroke. Each run first
    // cancels the timeout the previous run scheduled (the cleanup function below), so
    // only the last keystroke's timeout ever survives long enough to fire. That's the
    // whole debounce: wait 400ms after the user stops typing, not after every key.
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
            index.url({
                query: {
                    department_id: next.department_id ?? undefined,
                    status: next.status ?? undefined,
                    search: next.search ?? undefined,
                },
            }),
            {},
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    return (
        <>
            <Head title="My contracts" />

            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <Heading
                        title="My contracts"
                        description={`${contracts.meta.total} contract${contracts.meta.total === 1 ? '' : 's'}`}
                    />

                    <Button asChild>
                        <Link href={create()}>New contract</Link>
                    </Button>
                </div>

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

                    <div className="grid gap-2">
                        <Label htmlFor="status">Status</Label>

                        <Select
                            value={filters.status ?? 'all'}
                            onValueChange={(value) =>
                                navigate({
                                    status:
                                        value === 'all'
                                            ? null
                                            : (value as ContractStatus),
                                })
                            }
                        >
                            <SelectTrigger
                                id="status"
                                className="w-full sm:w-40"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All</SelectItem>
                                <SelectItem value="pending">Pending</SelectItem>
                                <SelectItem value="formalized">
                                    Formalized
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
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
                                    Deadline
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            {contracts.data.map((contract) => (
                                <tr
                                    key={contract.id}
                                    className="border-t align-top"
                                >
                                    <td className="px-4 py-2">
                                        <Link
                                            href={show(contract)}
                                            className="font-medium hover:underline"
                                        >
                                            {contract.reference}
                                        </Link>
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
                                        {contract.formalization_deadline ?? '—'}
                                    </td>
                                    <td className="px-4 py-2">
                                        <ContractActions contract={contract} />
                                    </td>
                                </tr>
                            ))}

                            {contracts.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="text-muted-foreground px-4 py-8 text-center"
                                    >
                                        No contracts match these filters.
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

ContractsIndex.layout = {
    breadcrumbs: [{ title: 'My contracts', href: index() }],
};
