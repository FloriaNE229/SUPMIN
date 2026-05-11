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
                'libelle' => 'Administrateur'
            ],

            [
                'name' => 'agent',
                'code' => 'AGENT',
                'libelle' => 'Agent'
            ],

            [
                'name' => 'superviseur',
                'code' => 'SUP',
                'libelle' => 'Superviseur'
            ],

        ];

        foreach ($roles as $role) {

            Role::firstOrCreate(

                [
                    'name' => $role['name'],
                    'guard_name' => 'sanctum'
                ],

                [
                    'id' => (string) Str::uuid(),
                    'code' => $role['code'],
                    'libelle' => $role['libelle']
                ]

            );
        }
    }
}