<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    const MAX_ATTEMPTS = 5;
    const LOCK_MINUTES = 30;

    /**
     * LOGIN avec verrouillage après 5 tentatives (RG-USR-006)
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants invalides',
                'errors'  => null
            ], 401);
        }

        // Vérifier si le compte est suspendu
        if ($user->statut === 'suspendu') {
            return response()->json([
                'success' => false,
                'message' => 'Compte suspendu. Contactez l\'administrateur.',
                'errors'  => null
            ], 403);
        }

        // Vérifier verrouillage temporaire
        if ($user->tentatives_echec >= self::MAX_ATTEMPTS) {
            $lockedUntil = $user->updated_at->addMinutes(self::LOCK_MINUTES);
            if (now()->lt($lockedUntil)) {
                $remaining = now()->diffInMinutes($lockedUntil, false);
                return response()->json([
                    'success' => false,
                    'message' => "Compte verrouillé. Réessayez dans {$remaining} minutes.",
                    'errors'  => null
                ], 423);
            } else {
                // Réinitialiser après expiration du verrou
                $user->update(['tentatives_echec' => 0]);
            }
        }

        // Vérifier le mot de passe
        if (!Hash::check($request->password, $user->mot_de_passe_hash)) {
            $user->increment('tentatives_echec');

            $remaining = self::MAX_ATTEMPTS - $user->tentatives_echec;
            $message = $remaining > 0
                ? "Identifiants invalides. {$remaining} tentative(s) restante(s)."
                : "Compte verrouillé pour " . self::LOCK_MINUTES . " minutes.";

            return response()->json([
                'success' => false,
                'message' => $message,
                'errors'  => null
            ], 401);
        }

        // Succès - réinitialiser les tentatives
        $user->update([
            'tentatives_echec' => 0,
            'date_derniere_connexion' => now(),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        // Charger le rôle pour la redirection front
        $user->load('roles');
        $role = $user->roles->first();

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => [
                'id'       => $user->id,
                'nom'      => $user->nom,
                'prenom'   => $user->prenom,
                'email'    => $user->email,
                'statut'   => $user->statut,
                'telephone'=> $user->telephone,
                'role'     => $role ? $role->libelle : null,
                'role_code'=> $role ? $role->name : null,
            ],
            'message' => 'Connexion réussie',
            'errors'  => null
        ]);
    }

    /**
     * REGISTER (admin uniquement - RG-USR-002)
     */
    public function register(Request $request)
    {
        $request->validate([
            'nom'       => 'required|string|max:100',
            'prenom'    => 'required|string|max:100',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:10',
            'telephone' => 'nullable|string|max:20',
            'role'      => 'required|string|exists:roles,name',
        ]);

        $user = User::create([
            'nom'               => $request->nom,
            'prenom'            => $request->prenom,
            'email'             => $request->email,
            'mot_de_passe_hash' => Hash::make($request->password),
            'telephone'         => $request->telephone,
            'statut'            => 'actif',
            'tentatives_echec'  => 0,
        ]);

        $user->assignRole($request->role);

        return response()->json([
            'success' => true,
            'data'    => $user->load('roles'),
            'message' => 'Utilisateur créé',
            'errors'  => null
        ], 201);
    }

    /**
     * LOGOUT
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnecté',
            'errors'  => null
        ]);
    }

    /**
     * ME
     */
    public function me(Request $request)
    {
        $user = $request->user()->load('roles');
        $role = $user->roles->first();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'        => $user->id,
                'nom'       => $user->nom,
                'prenom'    => $user->prenom,
                'email'     => $user->email,
                'telephone' => $user->telephone,
                'statut'    => $user->statut,
                'role'      => $role ? $role->libelle : null,
                'role_code' => $role ? $role->name : null,
            ],
            'message' => 'Utilisateur authentifié',
            'errors'  => null
        ]);
    }

    /**
     * RESET PASSWORD
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|exists:users,email',
            'password' => 'required|min:10',
        ]);

        $user = User::where('email', $request->email)->first();
        $user->update([
            'mot_de_passe_hash' => Hash::make($request->password),
            'tentatives_echec'  => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe réinitialisé',
            'errors'  => null
        ]);
    }
}
