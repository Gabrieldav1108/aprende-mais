<?php

namespace App\Models;

use App\Enums\AttachmentStatus;
use Database\Factories\AttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * A file the browser uploads straight to the bucket through a presigned URL; it stays pending until the upload is confirmed.
 *
 * @property int $id
 * @property string|null $attachable_type
 * @property int|null $attachable_id
 * @property int $uploader_id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property AttachmentStatus $status
 * @property Carbon|null $uploaded_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['uploader_id', 'disk', 'path', 'original_name', 'mime_type', 'size', 'status', 'uploaded_at'])]
class Attachment extends Model
{
    /** @use HasFactory<AttachmentFactory> */
    use HasFactory;

    /**
     * The disk new attachments are stored on.
     */
    public const string DISK = 'r2';

    /**
     * The largest file accepted, in bytes (20 MB).
     */
    public const int MAX_SIZE = 20 * 1024 * 1024;

    /**
     * The accepted MIME types and the extension each one is stored with.
     *
     * @var array<string, string>
     */
    public const array MIME_TYPES = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'text/plain' => 'txt',
        'application/zip' => 'zip',
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'status' => AttachmentStatus::class,
            'uploaded_at' => 'datetime',
        ];
    }

    /**
     * The record the file belongs to (an activity, a submission, ...); null until it is linked.
     *
     * @return MorphTo<Model, $this>
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    /**
     * Whether the file has been confirmed in the bucket.
     */
    public function isUploaded(): bool
    {
        return $this->status === AttachmentStatus::Uploaded;
    }
}
