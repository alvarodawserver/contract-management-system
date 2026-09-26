import { Head, Link } from '@inertiajs/react';
import { ContractMovementsTable } from '@/components/contract-movements-table';
import Heading from '@/components/heading';
import { Pagination } from '@/components/pagination';
import { index as contractsIndex, show } from '@/routes/contracts';
import { index as forContractMovements } from '@/routes/contracts/movements';
import type { Contract, ContractMovement, Paginated } from '@/types';

type Props = {
    contract: Contract;
    movements: Paginated<ContractMovement>;
};

export default function ContractMovements({ contract, movements }: Props) {
    return (
        <>
            <Head title={`Movements — ${contract.reference}`} />

            <div className="flex flex-col gap-4 p-4">
                <Heading
                    title={`Movements — ${contract.reference}`}
                    description={contract.title}
                />

                <Link href={show(contract)} className="text-sm hover:underline">
                    ← Back to contract
                </Link>

                <ContractMovementsTable
                    movements={movements}
                    showContract={false}
                />

                <Pagination meta={movements.meta} links={movements.links} />
            </div>
        </>
    );
}

// A function instead of a fixed object: Inertia calls it with this page's actual props, so the
// breadcrumb can show the real contract reference instead of a generic fixed label.
ContractMovements.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'My contracts', href: contractsIndex() },
        { title: props.contract.reference, href: show(props.contract) },
        { title: 'Movements', href: forContractMovements(props.contract) },
    ],
});
