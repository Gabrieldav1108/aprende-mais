<?php

namespace App\Repositories;

use App\Enums\AttachmentStatus;
use App\Exceptions\RepositoryException;
use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
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
                'path' => $this->newPath(Attachment::MIME_TYPES[$data['mime_type']]),
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
     * Link exactly the given attachments to the record: the new ones are linked and the ones it had that are not in the list
     * are removed, their objects leaving the bucket once the transaction commits. Validation must already have checked that
     * each id is free to link or already belongs to the record.
     *
     * @param  list<int>  $attachmentIds
     */
    public function syncTo(Model $attachable, array $attachmentIds): void
    {
        try {
            DB::transaction(function () use ($attachable, $attachmentIds): void {
                $linked = Attachment::query()
                    ->where('attachable_type', $attachable->getMorphClass())
                    ->where('attachable_id', $attachable->getKey());

                $removed = (clone $linked)->whereKeyNot($attachmentIds)->get();

                $removed->each(fn (Attachment $attachment) => $attachment->delete());

                Attachment::query()->whereKey($attachmentIds)->update([
                    'attachable_type' => $attachable->getMorphClass(),
                    'attachable_id' => $attachable->getKey(),
                ]);

                DB::afterCommit(fn () => $removed->each(
                    fn (Attachment $attachment) => Storage::disk($attachment->disk)->delete($attachment->path),
                ));
            });
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to link attachments.', [
                'attachable_type' => $attachable->getMorphClass(),
                'attachable_id' => $attachable->getKey(),
            ], $e);
        }
    }

    /**
     * Link a copy of each attachment to the record, its object copied inside the bucket, so the record owns its files
     * while the originals stay with the record they belong to.
     *
     * @param  iterable<Attachment>  $attachments
     */
    public function copyTo(Model $attachable, iterable $attachments): void
    {
        try {
            DB::transaction(function () use ($attachable, $attachments): void {
                foreach ($attachments as $attachment) {
                    $copy = $attachment->replicate();
                    $copy->path = $this->newPath(pathinfo($attachment->path, PATHINFO_EXTENSION));
                    $copy->attachable()->associate($attachable);

                    Storage::disk($attachment->disk)->copy($attachment->path, $copy->path);

                    $copy->save();
                }
            });
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to copy attachments.', [
                'attachable_type' => $attachable->getMorphClass(),
                'attachable_id' => $attachable->getKey(),
            ], $e);
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

    /**
     * A new unique object path in the bucket, under the current month.
     */
    private function newPath(string $extension): string
    {
        return 'attachments/'.now()->format('Y/m').'/'.Str::lower((string) Str::ulid()).'.'.$extension;
    }
}
