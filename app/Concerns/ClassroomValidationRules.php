<?php

namespace App\Concerns;

use App\Enums\ClassroomStatus;
use App\Enums\Shift;
use App\Enums\TermStatus;
use App\Models\Classroom;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ClassroomValidationRules
{
    use SchoolScopedRules;

    /**
     * Validation rules shared by classroom creation and update.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function classroomRules(?Classroom $classroom = null): array
    {
        $minimumCapacity = $classroom ? max(1, $classroom->classroomStudents()->count()) : 1;
        $courseId = $classroom?->course_id ?? $this->input('course_id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:255',
                Rule::unique('classrooms', 'code')
                    ->where('course_id', $courseId)
                    ->where('academic_term_id', $this->input('academic_term_id'))
                    ->whereNull('deleted_at')
                    ->ignore($classroom?->id),
            ],
            'academic_term_id' => [
                'required', 'integer',
                $this->existsInSchool('academic_terms')
                    ->whereIn('status', [TermStatus::Planned->value, TermStatus::Open->value]),
            ],
            'term_number' => ['required', 'integer', 'min:1', 'max:20'],
            'shift' => ['required', Rule::enum(Shift::class)],
            'status' => ['required', Rule::enum(ClassroomStatus::class)],
            'capacity' => ['required', 'integer', 'min:'.$minimumCapacity, 'max:1000'],
            'homeroom_teacher_id' => ['nullable', 'integer', $this->existsRoleInSchool('teachers')],
            'room' => ['nullable', 'string', 'max:255'],
        ];
    }
}
