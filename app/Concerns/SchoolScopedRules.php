<?php

namespace App\Concerns;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

trait SchoolScopedRules
{
    /**
     * Build an exists rule limited to non-deleted rows of the authenticated user's school.
     */
    protected function existsInSchool(string $table): Exists
    {
        return Rule::exists($table, 'id')
            ->whereNull('deleted_at')
            ->where('school_id', $this->user()?->school_id ?? 0);
    }

    /**
     * Build an exists rule for a role table (students, teachers) limited to people of the authenticated user's school.
     */
    protected function existsRoleInSchool(string $table): Exists
    {
        $schoolId = $this->user()?->school_id ?? 0;

        return Rule::exists($table, 'id')
            ->whereNull('deleted_at')
            ->whereIn('person_id', fn ($query) => $query->select('id')->from('people')->where('school_id', $schoolId)->whereNull('deleted_at'));
    }
}
