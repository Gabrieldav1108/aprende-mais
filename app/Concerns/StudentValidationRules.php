<?php

namespace App\Concerns;

use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Models\Person;
use App\Models\Student;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait StudentValidationRules
{
    use SchoolScopedRules;

    /**
     * Validation rules for the student data, shared by creation and update.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function studentRules(?Student $student = null): array
    {
        $schoolId = $student?->person->school_id ?? $this->user()->school_id;

        return [
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

    /**
     * The person that becomes a student must belong to the school, have the student role and not be a student yet.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function studentPersonRules(): array
    {
        return [
            'bail', 'required', 'integer',
            $this->existsInSchool('people'),
            function (string $attribute, mixed $value, Closure $fail): void {
                if (! Person::query()->find($value)?->user->hasRole(UserRole::Student->value)) {
                    $fail(__('A pessoa precisa ter o papel de aluno.'));
                }
            },
            Rule::unique('students', 'person_id')->whereNull('deleted_at'),
        ];
    }
}
