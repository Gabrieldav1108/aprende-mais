<?php

namespace App\Models;

use App\Concerns\BelongsToSchool;
use Database\Factories\KnowledgeAreaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * @property int $id
 * @property int $school_id
 * @property string $name
 * @property string|null $code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['school_id', 'name', 'code'])]
class KnowledgeArea extends Model implements Auditable
{
    /** @use HasFactory<KnowledgeAreaFactory> */
    use BelongsToSchool, HasFactory, \OwenIt\Auditing\Auditable, SoftDeletes;

    /**
     * Areas every school starts with (CNPq/CAPES major areas), keyed by code.
     *
     * @var array<string, string>
     */
    public const DEFAULTS = [
        'EXA' => 'Ciências Exatas e da Terra',
        'BIO' => 'Ciências Biológicas',
        'ENG' => 'Engenharias',
        'SAU' => 'Ciências da Saúde',
        'AGR' => 'Ciências Agrárias',
        'SOC' => 'Ciências Sociais Aplicadas',
        'HUM' => 'Ciências Humanas',
        'LLA' => 'Linguística, Letras e Artes',
    ];

    /**
     * Create the default areas for a school.
     */
    public static function createDefaultsFor(School $school): void
    {
        foreach (self::DEFAULTS as $code => $name) {
            self::withoutGlobalScopes()->create(['school_id' => $school->id, 'name' => $name, 'code' => $code]);
        }
    }

    /**
     * @return HasMany<Course, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /**
     * @return HasMany<Subject, $this>
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }
}
