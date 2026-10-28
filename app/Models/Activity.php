<?php

namespace App\Models;

use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * A task the teacher posts to the students of a classroom subject, with an optional due date and supporting files.
 *
 * @property int $id
 * @property int $classroom_subject_id
 * @property int $author_id
 * @property string $title
 * @property string $description
 * @property Carbon|null $due_at
 * @property bool $sent_by_email
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['classroom_subject_id', 'author_id', 'title', 'description', 'due_at', 'sent_by_email'])]
class Activity extends Model implements Auditable
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory, \OwenIt\Auditing\Auditable, SoftDeletes;

    /**
     * The most supporting files an activity can have.
     */
    public const int MAX_ATTACHMENTS = 10;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
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

    /**
     * The supporting files the teacher attached.
     *
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Every version the students sent, the earlier ones included.
     *
     * @return HasMany<Submission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Whether a submission may still be sent or changed without being late: there is no due date or it has not passed.
     */
    public function isOpen(): bool
    {
        return $this->due_at === null || $this->due_at->isFuture();
    }
}
