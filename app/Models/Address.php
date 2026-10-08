<?php

namespace App\Models;

use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * The single table of postal addresses. Owners reference it through an address_id.
 * Street data is compatible with the BrasilAPI CEP response.
 *
 * @property int $id
 * @property string|null $zip_code
 * @property string|null $street
 * @property string|null $neighborhood
 * @property string|null $city
 * @property string|null $state
 * @property string|null $ibge_code
 * @property string|null $ibge_state_code
 * @property string|null $number
 * @property string|null $complement
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'zip_code', 'street', 'neighborhood', 'city', 'state', 'ibge_code', 'ibge_state_code',
    'number', 'complement',
])]
class Address extends Model implements Auditable
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory, \OwenIt\Auditing\Auditable, SoftDeletes;
}
