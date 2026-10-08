<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Restricts a role model (student, teacher) to the authenticated user's school through its person.
 */
class PersonSchoolScope implements Scope
{
    /**
     * A school is a tenant, so an authenticated user without one sees nothing.
     * Guests (console, seeders, queue) are not restricted.
     *
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        if ($user === null) {
            return;
        }

        if ($user->school_id === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->whereIn(
            $model->qualifyColumn('person_id'),
            fn ($query) => $query->select('id')->from('people')->where('school_id', $user->school_id)->whereNull('deleted_at'),
        );
    }
}
