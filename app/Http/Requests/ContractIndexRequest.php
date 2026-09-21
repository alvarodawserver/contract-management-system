<?php

namespace App\Http\Requests;

use App\Enums\ContractStatus;
use App\Models\Contract;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContractIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Contract::class);
    }

    /**
     * Lapsed contracts are soft deleted, so they can not be listed by status: they live in the trash.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'status' => ['nullable', Rule::enum(ContractStatus::class)->except(ContractStatus::Lapsed)],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }
}
