<?php

namespace App\Concerns;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Models\Course;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait CourseValidationRules
{
    /**
     * Validation rules shared by course creation and update.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function courseRules(?Course $course = null): array
    {
        $schoolId = $course?->school_id ?? $this->user()->school_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:255',
                Rule::unique('courses', 'code')
                    ->when($schoolId !== null, fn ($rule) => $rule->where('school_id', $schoolId))
                    ->whereNull('deleted_at')
                    ->ignore($course?->id),
            ],
            'level' => ['required', Rule::enum(CourseLevel::class)],
            'duration_terms' => ['required', 'integer', 'min:1', 'max:20'],
            'status' => ['required', Rule::enum(CourseStatus::class)],
        ];
    }
}
