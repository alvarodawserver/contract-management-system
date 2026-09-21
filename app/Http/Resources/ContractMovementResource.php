<?php

namespace App\Http\Resources;

use App\Models\ContractMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ContractMovement
 */
class ContractMovementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action->value,
            'contract_reference' => $this->contract_reference,
            'changes' => $this->changes,
            'created_at' => $this->created_at?->toIso8601String(),
            'user' => $this->whenLoaded('user', fn (): ?array => $this->user === null
                ? null
                : ['id' => $this->user->id, 'name' => $this->user->name]),
        ];
    }
}
