<?php

namespace App\Concerns;

use App\Models\Subject;
use Illuminate\Validation\Rule;

trait SubjectValidationRules
{
    use SchoolScopedRules;

    /**
     * Validation rules for the catalog subject data, shared by creation and update.
     *
     * @return array<string, array<mixed>>
     */
    protected function subjectRules(?Subject $subject = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:255',
                Rule::unique('subjects', 'code')
                    ->where('school_id', $subject?->school_id ?? $this->user()->school_id)
                    ->whereNull('deleted_at')
                    ->ignore($subject?->id),
            ],
            'workload_hours' => ['required', 'integer', 'min:1', 'max:65535'],
            'teacher_id' => ['required', 'integer', $this->existsRoleInSchool('teachers')],
            'knowledge_area_id' => ['nullable', 'integer', $this->existsInSchool('knowledge_areas')],
        ];
    }
}
