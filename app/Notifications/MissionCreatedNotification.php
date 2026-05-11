<?php

namespace App\Notifications;

use App\Modules\Mission\Models\Mission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MissionCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(public Mission $mission)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'mission_created',
            'mission_id' => $this->mission->id,
            'reference' => $this->mission->reference,
            'message' => 'Une nouvelle mission a été créée.',
        ];
    }
}