<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;

/**
 * Anyone signed in may upload; a linked file follows the view permission of the record it belongs to. Admins pass through Gate::before.
 */
class AttachmentPolicy
{
    /**
     * Determine whether the user can request an upload URL.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can confirm the upload of the attachment.
     */
    public function complete(User $user, Attachment $attachment): bool
    {
        return $this->uploaded($user, $attachment) && ! $attachment->isUploaded();
    }

    /**
     * Determine whether the user can download the attachment.
     */
    public function download(User $user, Attachment $attachment): bool
    {
        if (! $attachment->isUploaded()) {
            return false;
        }

        if ($attachment->attachable_type === null) {
            return $this->uploaded($user, $attachment);
        }

        return $attachment->attachable !== null && $user->can('view', $attachment->attachable);
    }

    /**
     * Whether the user is the one who uploaded the attachment.
     */
    private function uploaded(User $user, Attachment $attachment): bool
    {
        return $attachment->uploader_id === $user->id;
    }
}
