import { useState } from 'react';
import { Form } from '@inertiajs/react';
import ContractController from '@/actions/App/Http/Controllers/ContractController';
import InputError from '@/components/input-error';
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
import type { Contract } from '@/types';

type Props = {
    contract: Contract;
};

/**
 * Fills in exactly the four fields that decide whether a contract is formalized. It submits
 * only those fields, not the whole edit form: the update route validates each field with
 * "sometimes", so nothing else on the contract is touched.
 */
export function FormalizeContractDialog({ contract }: Props) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button type="button" size="sm">
                    Formalize
                </Button>
            </DialogTrigger>

            <DialogContent>
                <DialogTitle>Formalize {contract.reference}</DialogTitle>
                <DialogDescription>
                    Fill in the final amount, both dates and the responsible
                    party. The contract becomes formalized as soon as all four
                    are set.
                </DialogDescription>

                <Form
                    {...ContractController.update.form(contract)}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor={`amount-${contract.id}`}>
                                    Final amount
                                </Label>

                                <Input
                                    id={`amount-${contract.id}`}
                                    name="amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    defaultValue={contract.amount ?? ''}
                                />

                                <InputError message={errors.amount} />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor={`start_date-${contract.id}`}
                                    >
                                        Start date
                                    </Label>

                                    <Input
                                        id={`start_date-${contract.id}`}
                                        name="start_date"
                                        type="date"
                                        defaultValue={contract.start_date ?? ''}
                                    />

                                    <InputError message={errors.start_date} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor={`end_date-${contract.id}`}>
                                        End date
                                    </Label>

                                    <Input
                                        id={`end_date-${contract.id}`}
                                        name="end_date"
                                        type="date"
                                        defaultValue={contract.end_date ?? ''}
                                    />

                                    <InputError message={errors.end_date} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor={`responsible-${contract.id}`}>
                                    Responsible
                                </Label>

                                <Input
                                    id={`responsible-${contract.id}`}
                                    name="responsible"
                                    defaultValue={contract.responsible ?? ''}
                                />

                                <InputError message={errors.responsible} />
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        Cancel
                                    </Button>
                                </DialogClose>

                                <Button type="submit" disabled={processing}>
                                    Formalize
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
