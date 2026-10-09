<?php

namespace App\Models;

use App\Concerns\BelongsToSchool;
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
 * @property string $name
 * @property string $code
 * @property int $school_id
 * @property int|null $knowledge_area_id
 * @property int|null $teacher_id
 * @property int $workload_hours
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['school_id', 'knowledge_area_id', 'teacher_id', 'name', 'code', 'workload_hours'])]
class Subject extends Model implements Auditable
{
    use BelongsToSchool, HasFactory, \OwenIt\Auditing\Auditable, SoftDeletes;

    /**
     * @return BelongsToMany<Course, $this>
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class)->withPivot('suggested_term_number')->withTimestamps();
    }

    /**
     * @return BelongsTo<KnowledgeArea, $this>
     */
    public function knowledgeArea(): BelongsTo
    {
        return $this->belongsTo(KnowledgeArea::class);
    }

    /**
     * @return BelongsTo<Teacher, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function classroomSubjects(): HasMany
    {
        return $this->hasMany(ClassroomSubject::class);
    }
}
