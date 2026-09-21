<?php

namespace App\Models;

use App\Enums\ContractStatus;
use App\Enums\RoleSlug;
use App\Observers\ContractObserver;
use Carbon\CarbonInterface;
use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $department_id
 * @property int $created_by
 * @property string $reference
 * @property string $title
 * @property string|null $description
 * @property string|null $type
 * @property string|null $responsible
 * @property CarbonInterface|null $expected_date
 * @property CarbonInterface|null $start_date
 * @property CarbonInterface|null $end_date
 * @property string|null $expected_amount
 * @property string|null $amount
 * @property CarbonInterface|null $formalization_deadline
 * @property CarbonInterface|null $formalized_at
 * @property CarbonInterface|null $last_reminder_sent_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read ContractStatus $status
 * @property-read array{years: int, months: int, days: int}|null $duration
 */
#[Fillable([
    'department_id',
    'created_by',
    'reference',
    'title',
    'description',
    'type',
    'responsible',
    'expected_date',
    'start_date',
    'end_date',
    'expected_amount',
    'amount',
    'formalization_deadline',
    'formalized_at',
    'last_reminder_sent_at',
])]
#[ObservedBy(ContractObserver::class)]
class Contract extends Model
{
    /** @use HasFactory<ContractFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expected_date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'expected_amount' => 'decimal:2',
            'amount' => 'decimal:2',
            'formalization_deadline' => 'date',
            'formalized_at' => 'date',
            'last_reminder_sent_at' => 'datetime',
        ];
    }

    /**
     * The date a new (or restored) contract has to be formalized by.
     */
    public static function defaultFormalizationDeadline(): CarbonInterface
    {
        return today()->addMonthsNoOverflow(config('contracts.formalization_months'));
    }

    /**
     * A contract is formalized once its final amount, both dates and its responsible are filled in.
     */
    public function isFormalized(): bool
    {
        return $this->missingFormalizationFields() === [];
    }

    /**
     * @return list<string>
     */
    public function missingFormalizationFields(): array
    {
        return array_values(array_filter(
            ['amount', 'start_date', 'end_date', 'responsible'],
            fn (string $field): bool => blank($this->{$field}),
        ));
    }

    public function isPastFormalizationDeadline(): bool
    {
        return $this->formalization_deadline?->lt(today()) ?? false;
    }

    /**
     * @return Attribute<ContractStatus, never>
     */
    protected function status(): Attribute
    {
        return Attribute::get(fn (): ContractStatus => $this->calculateStatus());
    }

    private function calculateStatus(): ContractStatus
    {
        return match (true) {
            $this->isFormalized() => ContractStatus::Formalized,
            $this->isPastFormalizationDeadline() => ContractStatus::Lapsed,
            default => ContractStatus::Pending,
        };
    }

    /**
     * Length of the contract, counting the end date as part of it, or null while the dates are unknown.
     *
     * @return Attribute<array{years: int, months: int, days: int}|null, never>
     */
    protected function duration(): Attribute
    {
        return Attribute::get(function (): ?array {
            if ($this->start_date === null || $this->end_date === null || $this->end_date->lt($this->start_date)) {
                return null;
            }

            $interval = $this->start_date->diff($this->end_date->addDay());

            return ['years' => $interval->y, 'months' => $interval->m, 'days' => $interval->d];
        });
    }

    /**
     * @param  Builder<Contract>  $query
     */
    #[Scope]
    protected function formalized(Builder $query): void
    {
        $query->whereNotNull('amount')
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->whereNotNull('responsible');
    }

    /**
     * @param  Builder<Contract>  $query
     */
    #[Scope]
    protected function unformalized(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereNull('amount')
            ->orWhereNull('start_date')
            ->orWhereNull('end_date')
            ->orWhereNull('responsible'));
    }

    /**
     * @param  Builder<Contract>  $query
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->unformalized()->where(fn (Builder $query) => $query
            ->whereNull('formalization_deadline')
            ->orWhereDate('formalization_deadline', '>=', today()));
    }

    /**
     * @param  Builder<Contract>  $query
     */
    #[Scope]
    protected function lapsed(Builder $query): void
    {
        $query->unformalized()->whereDate('formalization_deadline', '<', today());
    }

    /**
     * Admins see every contract; everyone else only those of their own department.
     *
     * @param  Builder<Contract>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        if (! $user->hasRole(RoleSlug::Admin)) {
            $query->where('department_id', $user->departmentId());
        }
    }

    /**
     * Keeps the contracts whose term overlaps the given period. While a contract has no
     * start or end date yet, its expected date stands in so it does not vanish from reports.
     *
     * @param  Builder<Contract>  $query
     */
    #[Scope]
    protected function overlapping(Builder $query, ?CarbonInterface $from, ?CarbonInterface $to): void
    {
        if ($from !== null) {
            $query->whereRaw('COALESCE(end_date, start_date, expected_date) >= ?', [$from->toDateString()]);
        }

        if ($to !== null) {
            $query->whereRaw('COALESCE(start_date, expected_date) <= ?', [$to->toDateString()]);
        }
    }

    /**
     * Search by title or reference, ignoring case. The conditions are grouped so that they
     * cannot escape the other filters applied to the same query.
     *
     * @param  Builder<Contract>  $query
     */
    #[Scope]
    protected function matching(Builder $query, string $term): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereLike('title', "%{$term}%")
            ->orWhereLike('reference', "%{$term}%"));
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ContractMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(ContractMovement::class);
    }
}
