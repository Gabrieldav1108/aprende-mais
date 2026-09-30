<?php

namespace App\Concerns;

use App\Enums\TermStatus;
use App\Models\AcademicTerm;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait AcademicTermValidationRules
{
    /**
     * Validation rules shared by academic term creation and update.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function academicTermRules(?AcademicTerm $term = null): array
    {
        $schoolId = $term?->school_id ?? $this->user()->school_id;

        return [
            'year' => ['required', 'integer', 'min:2000', 'max:2999'],
            'number' => [
                'required', 'integer', 'in:1,2',
                Rule::unique('academic_terms', 'number')
                    ->when($schoolId !== null, fn ($rule) => $rule->where('school_id', $schoolId))
                    ->where('year', $this->input('year'))
                    ->whereNull('deleted_at')
                    ->ignore($term?->id),
            ],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'status' => ['required', Rule::enum(TermStatus::class)],
        ];
    }
}
