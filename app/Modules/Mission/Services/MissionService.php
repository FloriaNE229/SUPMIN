<?php

namespace App\Modules\Mission\Services;

use App\Modules\Mission\Models\Mission;
use App\Modules\Mission\Models\MissionLog;
use App\Notifications\MissionCreatedNotification;
use App\Notifications\MissionUpdatedNotification;
use App\Models\User;
use Exception;

class MissionService
{
    /**
     * Créer une mission
     */
    public function create(array $data, $user)
    {
        $mission = Mission::create([
            'reference' => $data['reference'],
            'entity_id' => $data['entity_id'],
            'coordinateur_id' => $user->id,
            'objectif' => $data['objectif'],
            'axes_prioritaires' => $data['axes_prioritaires'] ?? null,
            'date_debut' => $data['date_debut'],
            'date_fin_prevue' => $data['date_fin_prevue'],
            'date_fin_effective' => $data['date_fin_effective'] ?? null,
            'statut' => $data['statut'],
            'annee_supervision' => $data['annee_supervision'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Assignation multiple des agents
        |--------------------------------------------------------------------------
        */
        if (!empty($data['agents'])) {

            $leaders = collect($data['agents'])
                ->filter(fn ($role) => $role === 'leader');

            if ($leaders->count() > 1) {
                throw new Exception(
                    "Une mission ne peut avoir qu’un seul leader."
                );
            }

            $syncData = [];

            foreach ($data['agents'] as $userId => $role) {
                $syncData[$userId] = [
                    'role' => $role,
                ];
            }

            $mission->agents()->sync($syncData);
        } elseif (!empty($data['agent_ids'])) {
            $mission->agents()->sync($data['agent_ids']);
        }

        /*
        |--------------------------------------------------------------------------
        | Log de création
        |--------------------------------------------------------------------------
        */
        MissionLog::create([
            'mission_id' => $mission->id,
            'user_id' => $user->id,
            'action' => 'created',
            'changes' => $mission->toArray(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Notification aux administrateurs
        |--------------------------------------------------------------------------
        */
        $admins = User::role('admin', 'sanctum')->get();

        foreach ($admins as $admin) {
            $admin->notify(
                new MissionCreatedNotification($mission)
            );
        }

        return $mission->load(['entity', 'agents']);
    }

    /**
     * Mettre à jour une mission
     */
    public function update(Mission $mission, array $data, $user)
    {
        $oldData = $mission->toArray();

        /*
        |--------------------------------------------------------------------------
        | Validation de la transition de statut
        |--------------------------------------------------------------------------
        */
        $currentStatus = $mission->statut;
        $newStatus = $data['statut'] ?? $currentStatus;

        $this->validateStatusTransition(
            $currentStatus,
            $newStatus
        );

        /*
        |--------------------------------------------------------------------------
        | Mise à jour de la mission
        |--------------------------------------------------------------------------
        */
        $mission->update([
            'reference' => $data['reference'] ?? $mission->reference,
            'entity_id' => $data['entity_id'] ?? $mission->entity_id,
            'objectif' => $data['objectif'] ?? $mission->objectif,
            'axes_prioritaires' => $data['axes_prioritaires'] ?? $mission->axes_prioritaires,
            'date_debut' => $data['date_debut'] ?? $mission->date_debut,
            'date_fin_prevue' => $data['date_fin_prevue'] ?? $mission->date_fin_prevue,
            'date_fin_effective' => $data['date_fin_effective'] ?? $mission->date_fin_effective,
            'statut' => $newStatus,
            'annee_supervision' => $data['annee_supervision'] ?? $mission->annee_supervision,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Mise à jour des agents
        |--------------------------------------------------------------------------
        */
        if (!empty($data['agents'])) {

            $leaders = collect($data['agents'])
                ->filter(fn ($role) => $role === 'leader');

            if ($leaders->count() > 1) {
                throw new Exception(
                    "Une mission ne peut avoir qu’un seul leader."
                );
            }

            $syncData = [];

            foreach ($data['agents'] as $userId => $role) {
                $syncData[$userId] = [
                    'role' => $role,
                ];
            }

            $mission->agents()->sync($syncData);
        } elseif (!empty($data['agent_ids'])) {
            $mission->agents()->sync($data['agent_ids']);
        }

        /*
        |--------------------------------------------------------------------------
        | Log de mise à jour
        |--------------------------------------------------------------------------
        */
        MissionLog::create([
            'mission_id' => $mission->id,
            'user_id' => $user->id,
            'action' => 'updated',
            'changes' => [
                'before' => $oldData,
                'after' => $mission->fresh()->toArray(),
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Notification aux administrateurs
        |--------------------------------------------------------------------------
        */
        $admins = User::role('admin', 'sanctum')->get();

        foreach ($admins as $admin) {
            $admin->notify(
                new MissionUpdatedNotification($mission)
            );
        }

        return $mission->load(['entity', 'agents']);
    }

    /**
     * Vérifie les transitions de statut autorisées
     */
    private function validateStatusTransition(
        string $currentStatus,
        string $newStatus
    ): void {
        // Aucun changement
        if ($currentStatus === $newStatus) {
            return;
        }

        $allowedTransitions = [
            'planifiee' => [
                'en_cours',
                'suspendue',
            ],
            'en_cours' => [
                'cloturee',
                'suspendue',
            ],
            'suspendue' => [
                'en_cours',
                'cloturee',
            ],
            'cloturee' => [],
        ];

        $allowed = $allowedTransitions[$currentStatus] ?? [];

        if (!in_array($newStatus, $allowed, true)) {
            throw new Exception(
                "Transition de statut interdite : "
                . "{$currentStatus} → {$newStatus}"
            );
        }
    }
}