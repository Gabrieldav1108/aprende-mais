<?php

namespace App\Concerns;

use App\Enums\StudentStatus;
use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait StudentValidationRules
{
    use PersonValidationRules, ProfileValidationRules, SchoolScopedRules;

    /**
     * Validation rules for the student and its person data, shared by creation and update.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function studentRules(?Student $student = null): array
    {
        $schoolId = $student?->person->school_id ?? $this->user()->school_id;

        return [
            ...$this->personRules($student?->person),
            'enrollment_number' => [
                'required', 'string', 'max:255',
                Rule::unique('students', 'enrollment_number')
                    ->where(fn ($query) => $query->whereIn(
                        'person_id',
                        fn ($people) => $people->select('id')->from('people')->where('school_id', $schoolId),
                    ))
                    ->whereNull('deleted_at')
                    ->ignore($student?->id),
            ],
            'course_id' => ['nullable', 'integer', $this->existsInSchool('courses')],
            'status' => ['required', Rule::enum(StudentStatus::class)],
        ];
    }
}
