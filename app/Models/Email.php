<?php

namespace App\Models;

use Database\Factories\EmailFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Contact e-mail of a person. The login e-mail stays in users.email.
 *
 * @property int $id
 * @property int $person_id
 * @property string $address
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['person_id', 'address'])]
class Email extends Model implements Auditable
{
    /** @use HasFactory<EmailFactory> */
    use HasFactory, \OwenIt\Auditing\Auditable, SoftDeletes;

    /**
     * @return BelongsTo<Person, $this>
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
