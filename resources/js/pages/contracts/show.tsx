import { Head, Link } from '@inertiajs/react';
import { ContractStatusBadge } from '@/components/contract-status-badge';
import { index as contractMovements } from '@/routes/contracts/movements';
import type { Contract } from '@/types';

type Props = {
    contract: Contract;
};

export default function ShowContract({ contract }: Props) {
    return (
        <>
            <Head title={`Contract ${contract.reference}`} />

            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <h1 className="text-2xl font-bold">Contract Data</h1>

                    <Link
                        href={contractMovements(contract)}
                        className="text-sm hover:underline"
                    >
                        View movements
                    </Link>
                </div>

                <div className="bg-neutral-primary-soft rounded-base border-default relative overflow-x-auto border shadow-xs">
                    <table className="text-body w-full text-left text-sm rtl:text-right">
                        <tbody>
                            <tr className="border-default border-b">
                                <th
                                    scope="row"
                                    className="bg-neutral-secondary-soft w-48 px-6 py-3 font-medium"
                                >
                                    Reference
                                </th>
                                <td className="px-6 py-3">
                                    {contract.reference}
                                </td>
                            </tr>
                            <tr className="border-default border-b">
                                <th
                                    scope="row"
                                    className="bg-neutral-secondary-soft w-48 px-6 py-3 font-medium"
                                >
                                    Title
                                </th>
                                <td className="px-6 py-3">{contract.title}</td>
                            </tr>
                            <tr className="border-default border-b">
                                <th
                                    scope="row"
                                    className="bg-neutral-secondary-soft w-48 px-6 py-3 font-medium"
                                >
                                    Description
                                </th>
                                <td className="px-6 py-3">
                                    {contract.description
                                        ? contract.description
                                        : '---'}
                                </td>
                            </tr>
                            <tr className="border-default border-b">
                                <th
                                    scope="row"
                                    className="bg-neutral-secondary-soft w-48 px-6 py-3 font-medium"
                                >
                                    Responsible
                                </th>
                                <td className="px-6 py-3">
                                    {contract.responsible}
                                </td>
                            </tr>
                            <tr className="border-default border-b">
                                <th
                                    scope="row"
                                    className="bg-neutral-secondary-soft w-48 px-6 py-3 font-medium"
                                >
                                    Expected date
                                </th>
                                <td className="px-6 py-3">
                                    {contract.expected_date
                                        ? contract.expected_date
                                        : '---'}
                                </td>
                            </tr>
                            <tr className="border-default border-b">
                                <th
                                    scope="row"
                                    className="bg-neutral-secondary-soft w-48 px-6 py-3 font-medium"
                                >
                                    Start date
                                </th>
                                <td className="px-6 py-3">
                                    {contract.start_date}
                                </td>
                            </tr>
                            <tr className="border-default border-b">
                                <th
                                    scope="row"
                                    className="bg-neutral-secondary-soft w-48 px-6 py-3 font-medium"
                                >
                                    Expected amount
                                </th>
                                <td className="px-6 py-3">
                                    {contract.expected_amount
                                        ? contract.expected_amount
                                        : '---'}
                                </td>
                            </tr>
                            <tr className="border-default border-b">
                                <th
                                    scope="row"
                                    className="bg-neutral-secondary-soft w-48 px-6 py-3 font-medium"
                                >
                                    Amount
                                </th>
                                <td className="px-6 py-3">{contract.amount}</td>
                            </tr>
                            <tr className="border-default border-b">
                                <th
                                    scope="row"
                                    className="bg-neutral-secondary-soft w-48 px-6 py-3 font-medium"
                                >
                                    Status
                                </th>
                                <td className="px-6 py-3">
                                    <ContractStatusBadge
                                        status={contract.status}
                                    />
                                </td>
                            </tr>
                            <tr>
                                <th
                                    scope="row"
                                    className="bg-neutral-secondary-soft w-48 px-6 py-3 font-medium"
                                >
                                    Creator
                                </th>
                                <td className="px-6 py-3">
                                    {contract.creator?.name}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}
