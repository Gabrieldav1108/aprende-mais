<?php

namespace App\Models;

use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * A notice the teacher posts to the students of a classroom subject.
 *
 * @property int $id
 * @property int $classroom_subject_id
 * @property int $author_id
 * @property string $title
 * @property string $body
 * @property bool $sent_by_email
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['classroom_subject_id', 'author_id', 'title', 'body', 'sent_by_email'])]
class Announcement extends Model implements Auditable
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory, \OwenIt\Auditing\Auditable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_by_email' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ClassroomSubject, $this>
     */
    public function classroomSubject(): BelongsTo
    {
        return $this->belongsTo(ClassroomSubject::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
