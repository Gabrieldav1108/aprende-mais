<?php

namespace App\Concerns;

use App\Enums\UserRole;
use App\Models\Person;
use App\Models\Teacher;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait TeacherValidationRules
{
    use SchoolScopedRules;

    /**
     * Validation rules for the teacher data, shared by creation and update.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function teacherRules(?Teacher $teacher = null): array
    {
        $schoolId = $teacher?->person->school_id ?? $this->user()->school_id;

        return [
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

    /**
     * The person that becomes a teacher must belong to the school, have the teacher role and not be a teacher yet.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function teacherPersonRules(): array
    {
        return [
            'bail', 'required', 'integer',
            $this->existsInSchool('people'),
            function (string $attribute, mixed $value, Closure $fail): void {
                if (! Person::query()->find($value)?->user->hasRole(UserRole::Teacher->value)) {
                    $fail(__('A pessoa precisa ter o papel de professor.'));
                }
            },
            Rule::unique('teachers', 'person_id')->whereNull('deleted_at'),
        ];
    }
}
