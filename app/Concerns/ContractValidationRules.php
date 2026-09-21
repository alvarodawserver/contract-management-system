<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\ConditionalRules;
use Illuminate\Validation\Rule;

trait ContractValidationRules
{
    /**
     * Get the validation rules for the data of a contract.
     *
     * Only the title is required: a contract is created early and completed as data becomes known.
     *
     * @return array<string, array<int, ValidationRule|ConditionalRules|string>>
     */
    protected function contractRules(): array
    {
        $amount = ['nullable', 'numeric', 'min:0', 'max:9999999999.99'];

        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['nullable', 'string', 'max:255'],
            'responsible' => ['nullable', 'string', 'max:255'],
            'expected_date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'end_date' => [
                'nullable',
                'date',
                Rule::when(filled($this->input('start_date')), 'after_or_equal:start_date'),
            ],
            'expected_amount' => $amount,
            'amount' => $amount,
            'formalization_deadline' => ['nullable', 'date'],
        ];
    }
}
