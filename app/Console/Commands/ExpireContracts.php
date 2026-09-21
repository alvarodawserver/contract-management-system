<?php

namespace App\Console\Commands;

use App\Models\Contract;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('contracts:expire')]
#[Description('Soft delete the contracts that were not formalized before their deadline')]
class ExpireContracts extends Command
{
    public function handle(): int
    {
        $expired = 0;

        Contract::lapsed()->each(function (Contract $contract) use (&$expired): void {
            $contract->delete();
            $expired++;
        });

        $this->info("Expired contracts: {$expired}");

        return self::SUCCESS;
    }
}
