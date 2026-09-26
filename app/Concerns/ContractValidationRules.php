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
     * @param  bool  $partial  When true, every field is validated with `sometimes`: a field
     *                         absent from the request is left untouched instead of being
     *                         validated as null, so a form that only sends a few fields (like
     *                         the "formalize" dialog) can't wipe out the rest of the contract.
     * @return array<string, array<int, ValidationRule|ConditionalRules|string>>
     */
    protected function contractRules(bool $partial = false): array
    {
        $amount = ['nullable', 'numeric', 'min:0', 'max:9999999999.99'];

        $rules = [
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

        if (! $partial) {
            return $rules;
        }

        return array_map(
            fn (array $fieldRules): array => ['sometimes', ...$fieldRules],
            $rules,
        );
    }
}
