import { Head } from '@inertiajs/react';
import { ContractMovementsTable } from '@/components/contract-movements-table';
import Heading from '@/components/heading';
import { Pagination } from '@/components/pagination';
import { index as movementsIndex } from '@/routes/movements';
import type { ContractMovement, Paginated } from '@/types';

type Props = {
    movements: Paginated<ContractMovement>;
};

export default function MovementsIndex({ movements }: Props) {
    return (
        <>
            <Head title="Movements" />

            <div className="flex flex-col gap-4 p-4">
                <Heading
                    title="Movements"
                    description="Every create, edit, delete, restore and permanent delete, across every contract you can see."
                />

                <ContractMovementsTable movements={movements} />

                <Pagination meta={movements.meta} links={movements.links} />
            </div>
        </>
    );
}

MovementsIndex.layout = {
    breadcrumbs: [{ title: 'Movements', href: movementsIndex() }],
};
