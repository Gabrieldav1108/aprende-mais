<?php

namespace App\Models;

use App\Concerns\BelongsToSchool;
use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Personal data of a user, shared by every role the user holds (student, teacher, ...).
 *
 * @property int $id
 * @property int $school_id
 * @property int $user_id
 * @property int|null $address_id
 * @property string $name
 * @property string|null $cpf_cnpj
 * @property Carbon|null $birth_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['school_id', 'user_id', 'address_id', 'name', 'cpf_cnpj', 'birth_date'])]
class Person extends Model implements Auditable
{
    /** @use HasFactory<PersonFactory> */
    use BelongsToSchool, HasFactory, \OwenIt\Auditing\Auditable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date:Y-m-d',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Address, $this>
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * @return HasMany<Phone, $this>
     */
    public function phones(): HasMany
    {
        return $this->hasMany(Phone::class);
    }

    /**
     * @return HasMany<Email, $this>
     */
    public function emails(): HasMany
    {
        return $this->hasMany(Email::class);
    }
}
