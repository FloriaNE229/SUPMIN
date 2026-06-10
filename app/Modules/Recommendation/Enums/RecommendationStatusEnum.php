<?php

namespace App\Modules\Recommendation\Enums;

enum RecommendationStatusEnum: string
{
    case OPEN = 'OPEN';
    case IN_PROGRESS = 'IN_PROGRESS';
    case RESOLVED = 'RESOLVED';
    case CLOSED = 'CLOSED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}