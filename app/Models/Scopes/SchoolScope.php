<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SchoolScope implements Scope
{
    /**
     * Restrict queries to the authenticated user's school.
     *
     * Users without a school (global users) are not restricted.
     *
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $schoolId = auth()->user()?->school_id;

        if ($schoolId !== null) {
            $builder->where($model->qualifyColumn('school_id'), $schoolId);
        }
    }
}
