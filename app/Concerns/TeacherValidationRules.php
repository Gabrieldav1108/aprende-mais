<?php

namespace App\Concerns;

use App\Models\Teacher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait TeacherValidationRules
{
    use PersonValidationRules, ProfileValidationRules;

    /**
     * Validation rules for the teacher and its person data, shared by creation and update.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function teacherRules(?Teacher $teacher = null): array
    {
        $schoolId = $teacher?->person->school_id ?? $this->user()->school_id;

        return [
            ...$this->personRules($teacher?->person),
            'registration_number' => [
                'required', 'string', 'max:255',
                Rule::unique('teachers', 'registration_number')
                    ->where(fn ($query) => $query->whereIn(
                        'person_id',
                        fn ($people) => $people->select('id')->from('people')->where('school_id', $schoolId),
                    ))
                    ->whereNull('deleted_at')
                    ->ignore($teacher?->id),
            ],
            'qualification' => ['nullable', 'string', 'max:255'],
        ];
    }
}
