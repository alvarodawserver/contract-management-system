<?php

namespace App\Http\Requests;

use App\Concerns\ContractValidationRules;
use App\Enums\RoleSlug;
use App\Models\Contract;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ConditionalRules;

class StoreContractRequest extends FormRequest
{
    use ContractValidationRules;

    public function authorize(): bool
    {
        return $this->user()->can('create', Contract::class);
    }

    /**
     * Only an admin chooses the department; everyone else creates in their own.
     *
     * @return array<string, array<int, ValidationRule|ConditionalRules|string>>
     */
    public function rules(): array
    {
        return [
            ...($this->user()->hasRole(RoleSlug::Admin)
                ? ['department_id' => ['required', 'integer', 'exists:departments,id']]
                : []),
            ...$this->contractRules(),
        ];
    }

    public function departmentId(): int
    {
        return $this->user()->hasRole(RoleSlug::Admin)
            ? $this->integer('department_id')
            : $this->user()->departmentId() ?? abort(403);
    }
}
