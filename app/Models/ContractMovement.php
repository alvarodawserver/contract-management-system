<?php

namespace App\Models;

use App\Enums\MovementAction;
use Database\Factories\ContractMovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $contract_id
 * @property string $contract_reference
 * @property int|null $user_id
 * @property MovementAction $action
 * @property array<string, array{old: mixed, new: mixed}>|null $changes
 * @property Carbon|null $created_at
 */
#[Fillable(['contract_id', 'contract_reference', 'user_id', 'action', 'changes'])]
class ContractMovement extends Model
{
    /** @use HasFactory<ContractMovementFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => MovementAction::class,
            'changes' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
