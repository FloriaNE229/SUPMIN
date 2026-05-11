<?php

namespace App\Modules\Mission\Services;

use App\Modules\Mission\Models\Mission;
use App\Models\AuditLog;
use App\Notifications\MissionCreatedNotification;
use App\Notifications\MissionUpdatedNotification;
use App\Models\User;

class MissionService
{
    /**
     * Création mission
     */
    public function create(array $data, $user)
    {
        $mission = Mission::create([

            'entity_id' => $data['entity_id'],

            'user_id' => $user->id,

            'title' => $data['title'],

            'description' => $data['description'] ?? null,

            'status' => $data['status'],

        ]);

        /*
        |--------------------------------------------------------------------------
        | ASSIGNATION AGENTS
        |--------------------------------------------------------------------------
        */

        if (!empty($data['agents'])) {

            // Vérifier un seul leader
            $leaders = collect($data['agents'])
                ->filter(fn($role) => $role === 'leader');

            if ($leaders->count() > 1) {

                throw new \Exception(
                    "Une mission ne peut avoir qu’un seul leader"
                );
            }

            // Sync pivot
            $syncData = [];

            foreach ($data['agents'] as $userId => $role) {

                $syncData[$userId] = [
                    'role' => $role
                ];
            }

            $mission->agents()->sync($syncData);
        }

        /*
        |--------------------------------------------------------------------------
        | AUDIT LOG
        |--------------------------------------------------------------------------
        */

        AuditLog::create([

            'user_id' => $user->id,

            'action' => 'mission_created',

            'model_type' => 'Mission',

            'model_id' => $mission->id,

            'new_values' => $mission->toArray(),

            'ip_address' => request()->ip()

        ]);

        /*
        |--------------------------------------------------------------------------
        | NOTIFICATIONS
        |--------------------------------------------------------------------------
        */

        $admins = User::role('admin')->get();

        foreach ($admins as $admin) {

            $admin->notify(
                new MissionCreatedNotification($mission)
            );
        }

        return $mission->load([
            'forms',
            'agents'
        ]);
    }

    /**
     * Mise à jour mission
     */
    public function update(
        $mission,
        array $data,
        $user
    ) {
        $oldData = $mission->toArray();

        $mission->update($data);

        /*
        |--------------------------------------------------------------------------
        | UPDATE AGENTS
        |--------------------------------------------------------------------------
        */

        if (!empty($data['agents'])) {

            // Vérifier un seul leader
            $leaders = collect($data['agents'])
                ->filter(fn($role) => $role === 'leader');

            if ($leaders->count() > 1) {

                throw new \Exception(
                    "Une mission ne peut avoir qu’un seul leader"
                );
            }

            // Sync pivot
            $syncData = [];

            foreach ($data['agents'] as $userId => $role) {

                $syncData[$userId] = [
                    'role' => $role
                ];
            }

            $mission->agents()->sync($syncData);
        }

        /*
        |--------------------------------------------------------------------------
        | AUDIT LOG
        |--------------------------------------------------------------------------
        */

        AuditLog::create([

            'user_id' => $user->id,

            'action' => 'mission_updated',

            'model_type' => 'Mission',

            'model_id' => $mission->id,

            'old_values' => $oldData,

            'new_values' => $mission->fresh()->toArray(),

            'ip_address' => request()->ip()

        ]);

        /*
        |--------------------------------------------------------------------------
        | NOTIFICATIONS
        |--------------------------------------------------------------------------
        */

        $admins = User::role('admin')->get();

        foreach ($admins as $admin) {

            $admin->notify(
                new MissionUpdatedNotification($mission)
            );
        }

        return $mission->load([
            'forms',
            'agents'
        ]);
    }
}