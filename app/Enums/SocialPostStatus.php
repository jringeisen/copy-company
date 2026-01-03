<?php

namespace App\Enums;

enum SocialPostStatus: string
{
    case Draft = 'draft';
    case Queued = 'queued';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Failed = 'failed';
}
