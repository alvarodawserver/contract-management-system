<?php

namespace Database\Factories;

use App\Enums\MovementAction;
use App\Models\Contract;
use App\Models\ContractMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractMovement>
 */
class ContractMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'contract_reference' => fn (array $attributes): string => Contract::withTrashed()
                ->whereKey($attributes['contract_id'])
                ->firstOrFail()
                ->reference,
            'user_id' => User::factory(),
            'action' => MovementAction::Created,
            'changes' => null,
        ];
    }
}
