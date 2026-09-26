import { Head, Link, router } from '@inertiajs/react';
import { ContractActions } from '@/components/contract-actions';
import { ContractStatusBadge } from '@/components/contract-status-badge';
import { DepartmentFilter } from '@/components/department-filter';
import Heading from '@/components/heading';
import { Pagination } from '@/components/pagination';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { show } from '@/routes/contracts';
import type { Contract, Department, Paginated } from '@/types';

type Filters = {
    department_id: number | null;
    from: string | null;
    to: string | null;
};

type DepartmentSummary = {
    id: number;
    name: string;
    code: string;
    contracts: number;
};

type Props = {
    filters: Filters;
    departments: Department[];
    totals: {
        formalized: number;
        pending: number;
        lapsed: number;
        amount: number;
    };
    upcoming: Contract[];
    byDepartment: DepartmentSummary[];
    contracts: Paginated<Contract>;
};

const currencyFormatter = new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'EUR',
});

export default function Dashboard({
    filters,
    departments,
    totals,
    upcoming,
    byDepartment,
    contracts,
}: Props) {
    function navigate(changes: Partial<Filters>) {
        const next = { ...filters, ...changes };

        router.get(
            dashboard.url({
                query: {
                    department_id: next.department_id ?? undefined,
                    from: next.from ?? undefined,
                    to: next.to ?? undefined,
                },
            }),
            {},
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Dashboard"
                    description="Every contract in your department, filtered by period."
                />

                <div className="flex flex-wrap items-end gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="from">From</Label>
                        <Input
                            id="from"
                            type="date"
                            defaultValue={filters.from ?? ''}
                            onChange={(event) =>
                                navigate({ from: event.target.value || null })
                            }
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="to">To</Label>
                        <Input
                            id="to"
                            type="date"
                            defaultValue={filters.to ?? ''}
                            onChange={(event) =>
                                navigate({ to: event.target.value || null })
                            }
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

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader>
                            <CardDescription>Formalized</CardDescription>
                            <CardTitle className="text-2xl">
                                {totals.formalized}
                            </CardTitle>
                        </CardHeader>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardDescription>Pending</CardDescription>
                            <CardTitle className="text-2xl">
                                {totals.pending}
                            </CardTitle>
                        </CardHeader>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardDescription>Lapsed</CardDescription>
                            <CardTitle className="text-2xl">
                                {totals.lapsed}
                            </CardTitle>
                        </CardHeader>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardDescription>Total amount</CardDescription>
                            <CardTitle className="text-2xl">
                                {currencyFormatter.format(totals.amount)}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    {byDepartment.length > 1 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>By department</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ul className="divide-y">
                                    {byDepartment.map((department) => (
                                        <li
                                            key={department.id}
                                            className="flex items-center justify-between py-2 text-sm"
                                        >
                                            <span>{department.name}</span>
                                            <span className="text-muted-foreground">
                                                {department.contracts} contract
                                                {department.contracts === 1
                                                    ? ''
                                                    : 's'}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </CardContent>
                        </Card>
                    )}

                    {upcoming.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Closest to their deadline</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ul className="divide-y">
                                    {upcoming.map((contract) => (
                                        <li
                                            key={contract.id}
                                            className="flex items-center justify-between gap-4 py-2 text-sm"
                                        >
                                            <Link
                                                href={show(contract)}
                                                className="hover:underline"
                                            >
                                                {contract.reference} —{' '}
                                                {contract.title}
                                            </Link>
                                            <span className="text-muted-foreground whitespace-nowrap">
                                                {
                                                    contract.formalization_deadline
                                                }
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </CardContent>
                        </Card>
                    )}
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

Dashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};
