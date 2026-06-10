<?php

namespace App\Notifications;

use App\Modules\Mission\Models\Mission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MissionUpdatedNotification extends Notification
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
            'type' => 'mission_updated',
            'mission_id' => $this->mission->id,
            'reference' => $this->mission->reference,
            'message' => 'Une mission a été mise à jour.',
        ];
    }
}