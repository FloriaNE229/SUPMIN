<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Modules\Entities\Models\Entity;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer DSI (entité d'origine de l'admin) et ANSSP (pour le responsable d'entité de test)
        $dsi = Entity::where('code', 'DSI')->first();
        $dnsp = Entity::where('code', 'DNSP')->first();
        $anssp = Entity::where('code', 'ANSSP')->first();

        $users = [
            [
                'nom' => 'User', 'prenom' => 'Admin',
                'email' => 'admin@gouv.bj', 'password' => 'admin123',
                'role' => 'admin', 'telephone' => '+229 97 00 00 01',
                'entity_id' => $dsi?->id,
            ],
            [
                'nom' => 'Martin', 'prenom' => 'Alice',
                'email' => 'coordinateur@gouv.bj', 'password' => 'coord123',
                'role' => 'coordinateur', 'telephone' => '+229 97 00 00 02',
                'entity_id' => $dnsp?->id,
            ],
            [
                'nom' => 'Dupont', 'prenom' => 'Jean',
                'email' => 'agent@gouv.bj', 'password' => 'agent123',
                'role' => 'agent', 'telephone' => '+229 97 00 00 03',
                'entity_id' => $dnsp?->id,
            ],
            [
                'nom' => 'Sènou', 'prenom' => 'Kodjo',
                'email' => 'validateur@gouv.bj', 'password' => 'valid123',
                'role' => 'validateur', 'telephone' => '+229 97 00 00 04',
                'entity_id' => $dsi?->id,
            ],
            [
                'nom' => 'Adjoua', 'prenom' => 'Ministre',
                'email' => 'decideur@gouv.bj', 'password' => 'decide123',
                'role' => 'decideur', 'telephone' => '+229 97 00 00 05',
                'entity_id' => $dsi?->id,
            ],
            [
                'nom' => 'Traoré', 'prenom' => 'Fatima',
                'email' => 'entite@gouv.bj', 'password' => 'entite123',
                'role' => 'responsable_entite', 'telephone' => '+229 97 00 00 06',
                'entity_id' => $anssp?->id,
            ],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'id' => (string) Str::uuid(),
                    'nom' => $data['nom'],
                    'prenom' => $data['prenom'],
                    'email' => $data['email'],
                    'mot_de_passe_hash' => Hash::make($data['password']),
                    'telephone' => $data['telephone'],
                    'entity_id' => $data['entity_id'],
                    'statut' => 'actif',
                    'tentatives_echec' => 0,
                    'compte_active' => true,
                    'mdp_activation' => null,
                    'tentatives_activation' => 0,
                    'compte_bloque' => false,
                ]
            );

            $user->syncRoles([$data['role']]);

            // Si responsable d'entité : lier ANSSP à ce responsable
            if ($data['role'] === 'responsable_entite' && $data['entity_id']) {
                Entity::where('id', $data['entity_id'])
                    ->update(['responsable_id' => $user->id]);
            }
        }
    }
}