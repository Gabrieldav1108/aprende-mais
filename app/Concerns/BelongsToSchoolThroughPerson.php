<?php

namespace App\Concerns;

use App\Models\Person;
use App\Models\Scopes\PersonSchoolScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * For role models that have no school_id of their own: the school comes from the person.
 */
trait BelongsToSchoolThroughPerson
{
    protected static function bootBelongsToSchoolThroughPerson(): void
    {
        static::addGlobalScope(new PersonSchoolScope);
    }

    /**
     * @return BelongsTo<Person, $this>
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
