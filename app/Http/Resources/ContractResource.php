<?php

namespace App\Http\Resources;

use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Contract
 */
class ContractResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'responsible' => $this->responsible,
            'status' => $this->status->value,
            'expected_date' => $this->expected_date?->toDateString(),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'duration' => $this->duration,
            'expected_amount' => $this->expected_amount,
            'amount' => $this->amount,
            'formalization_deadline' => $this->formalization_deadline?->toDateString(),
            'formalized_at' => $this->formalized_at?->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'department' => $this->whenLoaded('department', fn (): array => [
                'id' => $this->department->id,
                'name' => $this->department->name,
                'code' => $this->department->code,
            ]),
            'creator' => $this->whenLoaded('creator', fn (): array => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'movements' => ContractMovementResource::collection($this->whenLoaded('movements')),
            'can' => [
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
                'restore' => $user->can('restore', $this->resource),
                'forceDelete' => $user->can('forceDelete', $this->resource),
            ],
        ];
    }
}
