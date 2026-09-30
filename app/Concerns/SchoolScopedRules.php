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
        $schoolId = $this->user()?->school_id;

        return Rule::exists($table, 'id')
            ->whereNull('deleted_at')
            ->when($schoolId !== null, fn (Exists $rule) => $rule->where('school_id', $schoolId));
    }
}
