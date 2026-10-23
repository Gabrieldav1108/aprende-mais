<?php

namespace App\Enums;

enum AttachmentStatus: string
{
    case Pending = 'pending';
    case Uploaded = 'uploaded';
}
