import { router } from '@inertiajs/react';
import { destroy } from '@/routes/contracts';
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
import type { Contract } from '@/types';

type Props = {
    contract: Contract;
};

/**
 * A soft delete: the contract moves to the trash and a head or admin can still restore it
 * from there, so the confirmation is a lighter warning than the trash's permanent delete.
 */
export function DeleteContractDialog({ contract }: Props) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button type="button" variant="destructive" size="sm">
                    Delete
                </Button>
            </DialogTrigger>

            <DialogContent>
                <DialogTitle>Delete {contract.reference}?</DialogTitle>
                <DialogDescription>
                    It will move to the trash. A department head or admin can
                    restore it from there.
                </DialogDescription>

                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>

                    <Button
                        type="button"
                        variant="destructive"
                        onClick={() =>
                            router.delete(destroy(contract).url, {
                                preserveScroll: true,
                                // A hard reload rather than trusting an in-place update:
                                // deleting redirects away from this list, so it's worth the
                                // full page load to be certain the new page reflects it.
                                onSuccess: () => window.location.reload(),
                            })
                        }
                    >
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
