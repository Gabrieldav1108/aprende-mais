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
 * @property string $registration_number
 * @property string|null $qualification
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['person_id', 'registration_number', 'qualification'])]
class Teacher extends Model implements Auditable
{
    use BelongsToSchoolThroughPerson, HasFactory, \OwenIt\Auditing\Auditable, SoftDeletes;

    public function classroomSubjects(): HasMany
    {
        return $this->hasMany(ClassroomSubject::class);
    }
}
