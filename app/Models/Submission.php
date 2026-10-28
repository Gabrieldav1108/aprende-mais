<?php

namespace App\Models;

use Database\Factories\SubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * One version of a student's answer to an activity. Changing the answer stores a new version and keeps the
 * earlier ones, so the teacher sees everything the student sent; the newest one is the current answer.
 *
 * @property int $id
 * @property int $activity_id
 * @property int $student_id
 * @property string|null $content
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['activity_id', 'student_id', 'content'])]
class Submission extends Model implements Auditable
{
    /** @use HasFactory<SubmissionFactory> */
    use HasFactory, \OwenIt\Auditing\Auditable;

    /**
     * The most files a submission can have.
     */
    public const int MAX_ATTACHMENTS = 10;

    /**
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * The files the student sent.
     *
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
