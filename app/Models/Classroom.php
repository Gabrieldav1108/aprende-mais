<?php

namespace App\Models;

use App\Concerns\BelongsToSchool;
use App\Enums\ClassroomStatus;
use App\Enums\Shift;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
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
 * @property int $course_id
 * @property int $academic_term_id
 * @property string $name
 * @property string $code
 * @property int $term_number
 * @property Shift $shift
 * @property ClassroomStatus $status
 * @property int $capacity
 * @property int|null $homeroom_teacher_id
 * @property string|null $room
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'school_id', 'course_id', 'academic_term_id', 'name', 'code', 'term_number',
    'shift', 'status', 'capacity', 'homeroom_teacher_id', 'room',
])]
class Classroom extends Model implements Auditable
{
    use BelongsToSchool, HasFactory, \OwenIt\Auditing\Auditable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'shift' => Shift::class,
            'status' => ClassroomStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<AcademicTerm, $this>
     */
    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    /**
     * @return BelongsTo<Teacher, $this>
     */
    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'homeroom_teacher_id');
    }

    /**
     * @return HasMany<ClassroomSubject, $this>
     */
    public function classroomSubjects(): HasMany
    {
        return $this->hasMany(ClassroomSubject::class);
    }

    /**
     * @return HasMany<ClassroomStudent, $this>
     */
    public function classroomStudents(): HasMany
    {
        return $this->hasMany(ClassroomStudent::class);
    }

    /**
     * @return BelongsToMany<Student, $this>
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'classroom_students')
            ->withPivot(['enrolled_at', 'status'])
            ->withTimestamps();
    }

    /**
     * Limit to the classrooms the user may see: all of them with classrooms.view-all, otherwise the ones they teach.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        if ($user->can('classrooms.view-all')) {
            return;
        }

        $query->taughtBy($user->teacher);
    }

    /**
     * Limit to the classrooms where the teacher is the homeroom teacher or teaches a subject.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function taughtBy(Builder $query, ?Teacher $teacher): void
    {
        if ($teacher === null) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(fn (Builder $query) => $query
            ->where('homeroom_teacher_id', $teacher->id)
            ->orWhereHas('classroomSubjects', fn (Builder $query) => $query->where('teacher_id', $teacher->id)));
    }
}
