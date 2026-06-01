<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'nom' => 'User',
                'prenom' => 'Admin',
                'email' => 'admin@supmin.bj',
                'password' => 'admin123',
                'role' => 'admin',
                'telephone' => '+229 97 00 00 01',
            ],
            [
                'nom' => 'Martin',
                'prenom' => 'Alice',
                'email' => 'coordinateur@supmin.bj',
                'password' => 'coord123',
                'role' => 'coordinateur',
                'telephone' => '+229 97 00 00 02',
            ],
            [
                'nom' => 'Dupont',
                'prenom' => 'Jean',
                'email' => 'agent@supmin.bj',
                'password' => 'agent123',
                'role' => 'agent',
                'telephone' => '+229 97 00 00 03',
            ],
            [
                'nom' => 'Sènou',
                'prenom' => 'Kodjo',
                'email' => 'validateur@supmin.bj',
                'password' => 'valid123',
                'role' => 'validateur',
                'telephone' => '+229 97 00 00 04',
            ],
            [
                'nom' => 'Adjoua',
                'prenom' => 'Ministre',
                'email' => 'decideur@supmin.bj',
                'password' => 'decide123',
                'role' => 'decideur',
                'telephone' => '+229 97 00 00 05',
            ],
            [
                'nom' => 'Traoré',
                'prenom' => 'Fatima',
                'email' => 'entite@supmin.bj',
                'password' => 'entite123',
                'role' => 'responsable_entite',
                'telephone' => '+229 97 00 00 06',
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
                    'statut' => 'actif',
                    'tentatives_echec' => 0,
                ]
            );

            $user->syncRoles([$data['role']]);
        }
    }
}
