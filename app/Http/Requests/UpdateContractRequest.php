<?php

namespace App\Http\Requests;

use App\Concerns\ContractValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ConditionalRules;

class UpdateContractRequest extends FormRequest
{
    use ContractValidationRules;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('contract'));
    }

    /**
     * @return array<string, array<int, ValidationRule|ConditionalRules|string>>
     */
    public function rules(): array
    {
        return $this->contractRules();
    }
}
