<?php

namespace App\Modules\Mission\Enums;

enum MissionStatusEnum: string
{
    case PENDING = 'PENDING';
    case IN_PROGRESS = 'IN_PROGRESS';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::PENDING => in_array($newStatus, [
                self::IN_PROGRESS
            ]),

            self::IN_PROGRESS => in_array($newStatus, [
                self::COMPLETED,
                self::CANCELLED
            ]),

            self::COMPLETED => false,
            self::CANCELLED => false,
        };
    }

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'En attente',
            self::IN_PROGRESS => 'En cours',
            self::COMPLETED => 'Terminée',
            self::CANCELLED => 'Annulée',
        };
    }
}