<?php

namespace App\Models;

use App\Concerns\BelongsToSchool;
use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * @property int $id
 * @property int $school_id
 * @property int|null $knowledge_area_id
 * @property string $name
 * @property string $code
 * @property CourseLevel $level
 * @property int $duration_terms
 * @property CourseStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['school_id', 'knowledge_area_id', 'name', 'code', 'level', 'duration_terms', 'status'])]
class Course extends Model implements Auditable
{
    /** @use HasFactory<CourseFactory> */
    use BelongsToSchool, HasFactory, \OwenIt\Auditing\Auditable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => CourseLevel::class,
            'status' => CourseStatus::class,
        ];
    }

    /**
     * @return BelongsToMany<Subject, $this>
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class)->withPivot('suggested_term_number')->withTimestamps();
    }

    /**
     * @return BelongsTo<KnowledgeArea, $this>
     */
    public function knowledgeArea(): BelongsTo
    {
        return $this->belongsTo(KnowledgeArea::class);
    }

    /**
     * @return HasMany<Classroom, $this>
     */
    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }
}
