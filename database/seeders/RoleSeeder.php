<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'code' => 'ADMIN',
                'libelle' => 'Administrateur Système',
                'guard_name' => 'sanctum'
            ],
            [
                'name' => 'coordinateur',
                'code' => 'COORD',
                'libelle' => 'Coordinateur de Supervision',
                'guard_name' => 'sanctum'
            ],
            [
                'name' => 'agent',
                'code' => 'AGENT',
                'libelle' => 'Agent de Supervision',
                'guard_name' => 'sanctum'
            ],
            [
                'name' => 'validateur',
                'code' => 'VALID',
                'libelle' => 'Validateur',
                'guard_name' => 'sanctum'
            ],
            [
                'name' => 'decideur',
                'code' => 'DECI',
                'libelle' => 'Décideur Ministériel',
                'guard_name' => 'sanctum'
            ],
            [
                'name' => 'responsable_entite',
                'code' => 'RESP',
                'libelle' => "Responsable d'Entité",
                'guard_name' => 'sanctum'
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name'], 'guard_name' => $role['guard_name']],
                [
                    'id' => (string) Str::uuid(),
                    'code' => $role['code'],
                    'libelle' => $role['libelle'],
                ]
            );
        }
    }
}
