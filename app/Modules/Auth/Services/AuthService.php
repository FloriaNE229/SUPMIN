<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AuthService
{
    public function register(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // ✅ Assignation du rôle par défaut
        Role::firstOrCreate(['name' => 'agent']);
        $user->assignRole('agent');

        $token = $user->createToken('auth_token')->plainTextToken;

        return compact('user', 'token');
    }

    public function login(array $data)
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return null;
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return compact('user', 'token');
    }

    public function logout($user)
    {
        $user->tokens()->delete();
    }
}