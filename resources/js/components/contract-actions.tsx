import { Link } from '@inertiajs/react';
import { DeleteContractDialog } from '@/components/delete-contract-dialog';
import { FormalizeContractDialog } from '@/components/formalize-contract-dialog';
import { Button } from '@/components/ui/button';
import { edit } from '@/routes/contracts';
import { index as contractMovements } from '@/routes/contracts/movements';
import type { Contract } from '@/types';

type Props = {
    contract: Contract;
};

/**
 * The row-level actions shared by every contract list ("My contracts", the dashboard):
 * edit, formalize (only while it isn't yet), delete, and view the movement history.
 * Visibility of each one follows the `can` flags the backend already computed per contract.
 */
export function ContractActions({ contract }: Props) {
    return (
        <div className="flex flex-wrap gap-2">
            {contract.can.update && (
                <Button type="button" variant="outline" size="sm" asChild>
                    <Link href={edit(contract)}>Edit</Link>
                </Button>
            )}

            {contract.can.update && contract.status !== 'formalized' && (
                <FormalizeContractDialog contract={contract} />
            )}

            {contract.can.delete && (
                <DeleteContractDialog contract={contract} />
            )}

            <Button type="button" variant="outline" size="sm" asChild>
                <Link href={contractMovements(contract)}>Movements</Link>
            </Button>
        </div>
    );
}
