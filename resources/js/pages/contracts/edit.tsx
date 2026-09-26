import { useState } from 'react';
import { Form, Head } from '@inertiajs/react';
import ContractController from '@/actions/App/Http/Controllers/ContractController';
import { ContractStatusBadge } from '@/components/contract-status-badge';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/contracts';
import type { Contract, ContractStatus } from '@/types';

type Props = {
    contract: Contract;
};

type Duration = { years: number; months: number; days: number };

/**
 * Mirrors Contract::duration() in the backend: the end date counts as part of the contract,
 * so a full calendar year (Jan 1 to Dec 31) comes out as exactly "1 year".
 */
function calculateDuration(start: string, end: string): Duration | null {
    const startDate = new Date(start);
    const endDate = new Date(end);

    if (
        Number.isNaN(startDate.getTime()) ||
        Number.isNaN(endDate.getTime()) ||
        endDate < startDate
    ) {
        return null;
    }

    endDate.setDate(endDate.getDate() + 1);

    let years = endDate.getFullYear() - startDate.getFullYear();
    let months = endDate.getMonth() - startDate.getMonth();
    let days = endDate.getDate() - startDate.getDate();

    if (days < 0) {
        months -= 1;
        days += new Date(
            endDate.getFullYear(),
            endDate.getMonth(),
            0,
        ).getDate();
    }

    if (months < 0) {
        years -= 1;
        months += 12;
    }

    return { years, months, days };
}

function formatDuration(duration: Duration): string {
    const parts = [
        duration.years > 0 &&
            `${duration.years} year${duration.years === 1 ? '' : 's'}`,
        duration.months > 0 &&
            `${duration.months} month${duration.months === 1 ? '' : 's'}`,
        duration.days > 0 &&
            `${duration.days} day${duration.days === 1 ? '' : 's'}`,
    ].filter(Boolean);

    return parts.length > 0 ? parts.join(', ') : 'Less than a day';
}

export default function EditContract({ contract }: Props) {
    const [amount, setAmount] = useState(contract.amount ?? '');
    const [startDate, setStartDate] = useState(contract.start_date ?? '');
    const [endDate, setEndDate] = useState(contract.end_date ?? '');
    const [responsible, setResponsible] = useState(contract.responsible ?? '');
    const [formalizationDeadline, setFormalizationDeadline] = useState(
        contract.formalization_deadline ?? '',
    );

    // Derived from the state above, recomputed on every render. No useEffect: these values
    // don't come from outside React (a request, a timer, a subscription), they are a plain
    // calculation over data we already have, so React can just work it out while rendering.
    const isFormalized =
        amount !== '' &&
        startDate !== '' &&
        endDate !== '' &&
        responsible !== '';
    const isPastDeadline =
        formalizationDeadline !== '' &&
        new Date(formalizationDeadline) < new Date();
    const status: ContractStatus = isFormalized
        ? 'formalized'
        : isPastDeadline
          ? 'lapsed'
          : 'pending';
    const duration =
        startDate !== '' && endDate !== ''
            ? calculateDuration(startDate, endDate)
            : null;

    return (
        <>
            <Head title={`Edit ${contract.reference}`} />

            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center gap-3">
                    <Heading
                        title={`Edit ${contract.reference}`}
                        description={contract.title}
                    />

                    <ContractStatusBadge status={status} />
                </div>

                {duration && (
                    <p className="text-muted-foreground text-sm">
                        Duration: {formatDuration(duration)}
                    </p>
                )}

                <Form
                    {...ContractController.update.form(contract)}
                    className="max-w-2xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="title">Title</Label>

                                <Input
                                    id="title"
                                    name="title"
                                    required
                                    maxLength={200}
                                    defaultValue={contract.title}
                                />

                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>

                                <textarea
                                    id="description"
                                    name="description"
                                    rows={3}
                                    defaultValue={contract.description ?? ''}
                                    className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:border-destructive flex w-full rounded-md border bg-transparent px-3 py-2 text-base shadow-xs outline-none focus-visible:ring-[3px] md:text-sm"
                                />

                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="type">Type</Label>

                                    <Input
                                        id="type"
                                        name="type"
                                        defaultValue={contract.type ?? ''}
                                        placeholder="e.g. Works, Services"
                                    />

                                    <InputError message={errors.type} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="responsible">
                                        Responsible
                                    </Label>

                                    <Input
                                        id="responsible"
                                        name="responsible"
                                        value={responsible}
                                        onChange={(event) =>
                                            setResponsible(event.target.value)
                                        }
                                        placeholder="Company or entity"
                                    />

                                    <InputError message={errors.responsible} />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="expected_date">
                                        Expected date
                                    </Label>

                                    <Input
                                        id="expected_date"
                                        name="expected_date"
                                        type="date"
                                        defaultValue={
                                            contract.expected_date ?? ''
                                        }
                                    />

                                    <InputError
                                        message={errors.expected_date}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="start_date">
                                        Start date
                                    </Label>

                                    <Input
                                        id="start_date"
                                        name="start_date"
                                        type="date"
                                        value={startDate}
                                        onChange={(event) =>
                                            setStartDate(event.target.value)
                                        }
                                    />

                                    <InputError message={errors.start_date} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="end_date">End date</Label>

                                    <Input
                                        id="end_date"
                                        name="end_date"
                                        type="date"
                                        value={endDate}
                                        onChange={(event) =>
                                            setEndDate(event.target.value)
                                        }
                                    />

                                    <InputError message={errors.end_date} />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="expected_amount">
                                        Expected amount
                                    </Label>

                                    <Input
                                        id="expected_amount"
                                        name="expected_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        defaultValue={
                                            contract.expected_amount ?? ''
                                        }
                                    />

                                    <InputError
                                        message={errors.expected_amount}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="amount">Final amount</Label>

                                    <Input
                                        id="amount"
                                        name="amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={amount}
                                        onChange={(event) =>
                                            setAmount(event.target.value)
                                        }
                                    />

                                    <InputError message={errors.amount} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="formalization_deadline">
                                        Formalization deadline
                                    </Label>

                                    <Input
                                        id="formalization_deadline"
                                        name="formalization_deadline"
                                        type="date"
                                        value={formalizationDeadline}
                                        onChange={(event) =>
                                            setFormalizationDeadline(
                                                event.target.value,
                                            )
                                        }
                                    />

                                    <InputError
                                        message={errors.formalization_deadline}
                                    />
                                </div>
                            </div>

                            <Button type="submit" disabled={processing}>
                                Save changes
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

EditContract.layout = {
    breadcrumbs: [
        { title: 'My contracts', href: index() },
        { title: 'Edit contract', href: index() },
    ],
};
