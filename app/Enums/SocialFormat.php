<?php

namespace App\Enums;

enum SocialFormat: string
{
    case Feed = 'feed';
    case Story = 'story';
    case Reel = 'reel';
    case Carousel = 'carousel';
    case Pin = 'pin';
    case Thread = 'thread';
}
