<?php

namespace App\Enums;

enum MarketingStrategyStatus: string
{
    case Pending = 'pending';
    case Generating = 'generating';
    case Completed = 'completed';
    case Failed = 'failed';
}
