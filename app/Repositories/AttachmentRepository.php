<?php

namespace App\Repositories;

use App\Enums\AttachmentStatus;
use App\Exceptions\RepositoryException;
use App\Models\Attachment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class AttachmentRepository
{
    /**
     * Register a file the user is about to upload. The stored extension comes from the MIME type, never from the user's file name.
     *
     * @param  array{name: string, mime_type: string, size: int}  $data
     */
    public function createPending(User $uploader, array $data): Attachment
    {
        try {
            return Attachment::create([
                'uploader_id' => $uploader->id,
                'disk' => Attachment::DISK,
                'path' => 'attachments/'.now()->format('Y/m').'/'.Str::lower((string) Str::ulid()).'.'.Attachment::MIME_TYPES[$data['mime_type']],
                'original_name' => $data['name'],
                'mime_type' => $data['mime_type'],
                'size' => $data['size'],
                'status' => AttachmentStatus::Pending,
            ]);
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to register attachment.', ['uploader_id' => $uploader->id], $e);
        }
    }

    /**
     * Mark the file as confirmed in the bucket, keeping the size the bucket reports.
     */
    public function markUploaded(Attachment $attachment, int $size): Attachment
    {
        try {
            $attachment->update([
                'size' => $size,
                'status' => AttachmentStatus::Uploaded,
                'uploaded_at' => now(),
            ]);

            return $attachment;
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to confirm attachment upload.', ['attachment_id' => $attachment->id], $e);
        }
    }

    /**
     * Remove the attachment and its object from the bucket.
     */
    public function discard(Attachment $attachment): void
    {
        try {
            DB::transaction(function () use ($attachment): void {
                $attachment->delete();

                Storage::disk($attachment->disk)->delete($attachment->path);
            });
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to discard attachment.', ['attachment_id' => $attachment->id], $e);
        }
    }
}
