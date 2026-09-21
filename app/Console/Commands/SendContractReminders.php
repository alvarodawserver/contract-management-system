<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Notifications\ContractFormalizationReminder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('contracts:send-reminders')]
#[Description('Remind the creator of each unformalized contract how long is left to formalize it')]
class SendContractReminders extends Command
{
    /**
     * Reminders stop once less than this many days remain before the deadline.
     */
    private const MINIMUM_DAYS_LEFT = 7;

    /**
     * A contract reminded within this many days is not reminded again.
     */
    private const DAYS_BETWEEN_REMINDERS = 6;

    public function handle(): int
    {
        $sent = 0;

        Contract::unformalized()
            ->whereDate('formalization_deadline', '>=', today()->addDays(self::MINIMUM_DAYS_LEFT))
            ->where(fn ($query) => $query
                ->whereNull('last_reminder_sent_at')
                ->orWhere('last_reminder_sent_at', '<=', now()->subDays(self::DAYS_BETWEEN_REMINDERS)))
            ->with(['creator', 'department'])
            ->each(function (Contract $contract) use (&$sent): void {
                $contract->creator->notify(new ContractFormalizationReminder($contract));

                $contract->forceFill(['last_reminder_sent_at' => now()])->saveQuietly();
                $sent++;
            });

        $this->info("Reminders sent: {$sent}");

        return self::SUCCESS;
    }
}
