<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * REGISTER
     */
    public function register(Request $request)
    {
        $request->validate([
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6'
        ]);

        $user = User::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'mot_de_passe_hash' => Hash::make($request->password),
            'statut' => 'actif'
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user
        ]);
    }

    /**
     * LOGIN
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->mot_de_passe_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants invalides'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user
        ]);
    }

    /**
     * LOGOUT
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnecté'
        ]);
    }

    /**
     * ME
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user()
        ]);
    }

    /**
     * RESET PASSWORD (simple version)
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:6'
        ]);

        $user = User::where('email', $request->email)->first();

        $user->update([
            'mot_de_passe_hash' => Hash::make($request->password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe réinitialisé'
        ]);
    }
}