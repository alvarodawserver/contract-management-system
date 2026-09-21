<?php

namespace App\Observers;

use App\Enums\MovementAction;
use App\Models\Contract;
use App\Models\ContractMovement;
use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ContractObserver
{
    /**
     * Fields that change as a side effect and must not be reported as a change.
     *
     * @var list<string>
     */
    private const IGNORED_FIELDS = ['updated_at', 'deleted_at', 'last_reminder_sent_at'];

    public function creating(Contract $contract): void
    {
        if (blank($contract->reference)) {
            $contract->reference = $this->nextReference();
        }

        $contract->formalization_deadline ??= Contract::defaultFormalizationDeadline();
    }

    /**
     * Keeps formalized_at in sync: set when the contract becomes complete, cleared when it stops being so.
     */
    public function saving(Contract $contract): void
    {
        $contract->formalized_at = $contract->isFormalized()
            ? $contract->formalized_at ?? today()
            : null;
    }

    public function created(Contract $contract): void
    {
        $this->record($contract, MovementAction::Created);
    }

    public function updated(Contract $contract): void
    {
        $changes = $this->changedFields($contract);

        // Restoring saves the model, so it surfaces here as a change of deleted_at. It is reported
        // from this event, and not from restored(), because there the original values are gone.
        if (array_key_exists('deleted_at', $contract->getChanges())) {
            $this->record($contract, MovementAction::Restored, $changes ?: null);

            return;
        }

        if ($changes !== []) {
            $this->record($contract, MovementAction::Updated, $changes);
        }
    }

    public function deleted(Contract $contract): void
    {
        if ($contract->isForceDeleting()) {
            return;
        }

        $this->record($contract, MovementAction::Deleted);
    }

    /**
     * A contract restored after missing its deadline gets a fresh formalization period.
     */
    public function restoring(Contract $contract): void
    {
        if (! $contract->isFormalized() && $contract->isPastFormalizationDeadline()) {
            $contract->formalization_deadline = Contract::defaultFormalizationDeadline();
        }
    }

    public function forceDeleted(Contract $contract): void
    {
        $this->record($contract, MovementAction::ForceDeleted);
    }

    /**
     * @return array<string, array{old: mixed, new: mixed}>
     */
    private function changedFields(Contract $contract): array
    {
        $changes = [];

        foreach (array_keys($contract->getChanges()) as $field) {
            if (in_array($field, self::IGNORED_FIELDS, true)) {
                continue;
            }

            $changes[$field] = [
                'old' => $this->normalize($contract, $field, $contract->getOriginal($field)),
                'new' => $this->normalize($contract, $field, $contract->getAttribute($field)),
            ];
        }

        return $changes;
    }

    /**
     * @param  array<string, array{old: mixed, new: mixed}>|null  $changes
     */
    private function record(Contract $contract, MovementAction $action, ?array $changes = null): void
    {
        ContractMovement::create([
            'contract_id' => $action === MovementAction::ForceDeleted ? null : $contract->getKey(),
            'contract_reference' => $contract->reference,
            'user_id' => Auth::id(),
            'action' => $action,
            'changes' => $changes,
        ]);
    }

    private function nextReference(): string
    {
        $prefix = 'CT-'.today()->year.'-';
        $last = max(
            Contract::withTrashed()->where('reference', 'like', $prefix.'%')->max('reference'),
            ContractMovement::where('contract_reference', 'like', $prefix.'%')->max('contract_reference'),
        );
        $next = $last === null ? 1 : (int) Str::after($last, $prefix) + 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function normalize(Contract $contract, string $field, mixed $value): mixed
    {
        return match (true) {
            $value instanceof BackedEnum => $value->value,
            $value instanceof CarbonInterface => $contract->getCasts()[$field] === 'date'
                ? $value->toDateString()
                : $value->toDateTimeString(),
            default => $value,
        };
    }
}
