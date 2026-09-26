import { Form, Head } from '@inertiajs/react';
import ContractController from '@/actions/App/Http/Controllers/ContractController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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
import { create, index } from '@/routes/contracts';
import type { Department } from '@/types';

type Props = {
    departments: Department[];
    formalizationMonths: number;
};

export default function CreateContract({
    departments,
    formalizationMonths,
}: Props) {
    return (
        <>
            <Head title="Create contract" />

            <div className="flex flex-col gap-4 p-4">
                <Heading
                    title="Create contract"
                    description={`Only the title is required. You have ${formalizationMonths} months to formalize it.`}
                />

                <Form
                    {...ContractController.store.form()}
                    className="max-w-xl space-y-6"
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
                                    placeholder="e.g. Street lighting renewal"
                                />

                                <InputError message={errors.title} />
                            </div>

                            {departments.length > 1 && (
                                <div className="grid gap-2">
                                    <Label htmlFor="department_id">
                                        Department
                                    </Label>

                                    <Select
                                        name="department_id"
                                        defaultValue={departments[0]?.id.toString()}
                                    >
                                        <SelectTrigger
                                            id="department_id"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="Select a department" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {departments.map((department) => (
                                                <SelectItem
                                                    key={department.id}
                                                    value={department.id.toString()}
                                                >
                                                    {department.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>

                                <textarea
                                    id="description"
                                    name="description"
                                    rows={3}
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
                                    />

                                    <InputError message={errors.start_date} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="end_date">End date</Label>

                                    <Input
                                        id="end_date"
                                        name="end_date"
                                        type="date"
                                    />

                                    <InputError message={errors.end_date} />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
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
                                    />

                                    <InputError message={errors.amount} />
                                </div>
                            </div>

                            <Button type="submit" disabled={processing}>
                                Create contract
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

CreateContract.layout = {
    breadcrumbs: [
        { title: 'My contracts', href: index() },
        { title: 'Create contract', href: create() },
    ],
};
