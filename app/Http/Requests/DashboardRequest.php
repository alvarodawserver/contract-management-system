<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ConditionalRules;
use Illuminate\Validation\Rule;

class DashboardRequest extends FormRequest
{
    /**
     * The dashboard is a management view: department heads and admins only.
     * A delegated employee already has their own contracts under "My contracts".
     */
    public function authorize(): bool
    {
        return $this->user()->isManager();
    }

    /**
     * @return array<string, array<int, ValidationRule|ConditionalRules|string>>
     */
    public function rules(): array
    {
        return [
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', Rule::when(filled($this->input('from')), 'after_or_equal:from')],
        ];
    }
}
