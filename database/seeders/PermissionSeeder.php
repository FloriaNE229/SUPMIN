<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [

            'users.read',
            'users.create',
            'users.update',
            'users.delete',

            'missions.read',
            'missions.create',
            'missions.update',
            'missions.validate',

            'reports.export',

        ];

        foreach ($permissions as $permission) {

            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'sanctum'
            ]);
        }
    }
}