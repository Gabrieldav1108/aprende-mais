<?php

namespace App\Models;

use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['name', 'code'])]
class School extends Model implements Auditable
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory, \OwenIt\Auditing\Auditable, SoftDeletes;

    /**
     * Every new school starts with the default knowledge areas.
     */
    protected static function booted(): void
    {
        static::created(fn (School $school) => KnowledgeArea::createDefaultsFor($school));
    }

    /**
     * @return HasMany<KnowledgeArea, $this>
     */
    public function knowledgeAreas(): HasMany
    {
        return $this->hasMany(KnowledgeArea::class);
    }

    /**
     * @return HasMany<Course, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
