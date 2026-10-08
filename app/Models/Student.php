<?php

namespace App\Models;

use App\Concerns\BelongsToSchoolThroughPerson;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * @property int $id
 * @property int $person_id
 * @property string|null $enrollment_number
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['person_id', 'enrollment_number'])]
class Student extends Model implements Auditable
{
    use BelongsToSchoolThroughPerson, HasFactory, \OwenIt\Auditing\Auditable, SoftDeletes;

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
}
