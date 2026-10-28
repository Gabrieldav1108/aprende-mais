<?php

namespace App\Models;

use App\Concerns\BelongsToSchoolThroughPerson;
use App\Enums\ClassroomStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * @property int $id
 * @property int $person_id
 * @property int|null $course_id
 * @property string|null $enrollment_number
 * @property StudentStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['person_id', 'course_id', 'enrollment_number', 'status'])]
class Student extends Model implements Auditable
{
    use BelongsToSchoolThroughPerson, HasFactory, \OwenIt\Auditing\Auditable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StudentStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function classroomStudents(): HasMany
    {
        return $this->hasMany(ClassroomStudent::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function attendences(): HasMany
    {
        return $this->hasMany(Attendence::class);
    }

    /**
     * @return HasMany<Submission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Whether the student has an active enrollment in the classroom.
     */
    public function isActivelyEnrolledIn(int $classroomId): bool
    {
        return $this->classroomStudents()
            ->where('classroom_id', $classroomId)
            ->where('status', EnrollmentStatus::Active)
            ->exists();
    }

    /**
     * Limit to the students the user may see: all of them with students.view-all, otherwise the ones enrolled in an active classroom they teach.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        if ($user->can('students.view-all')) {
            return;
        }

        $classrooms = Classroom::query()
            ->where('status', ClassroomStatus::Active)
            ->taughtBy($user->teacher)
            ->select('id');

        $query->whereHas('classroomStudents', fn (Builder $query) => $query->whereIn('classroom_id', $classrooms));
    }
}
