<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    const MAX_ATTEMPTS = 5;
    const LOCK_MINUTES = 30;
    const MAX_ACTIVATION_ATTEMPTS = 3;

    /**
     * Génère un mot de passe d'activation unique
     */
    private function generateActivationPassword(): string
    {
        return 'Supmin#' . strtoupper(Str::random(7));
    }

    /**
     * LOGIN avec vérification rôle + activation
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
            'role'     => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants invalides',
                'errors'  => null
            ], 401);
        }

        // Vérification du rôle sélectionné
        $userRole = $user->roles->first();
        if (!$userRole || $userRole->libelle !== $request->role) {
            return response()->json([
                'success' => false,
                'message' => 'Le rôle sélectionné ne correspond pas à votre compte.',
                'errors'  => null
            ], 403);
        }

        // Vérifier si le compte est bloqué (3 tentatives d'activation échouées)
        if ($user->compte_bloque) {
            return response()->json([
                'success' => false,
                'message' => 'Compte bloqué après 3 tentatives échouées. Contactez l\'administrateur.',
                'errors'  => null
            ], 423);
        }

        // Vérifier si le compte est suspendu
        if ($user->statut === 'suspendu') {
            return response()->json([
                'success' => false,
                'message' => 'Compte suspendu. Contactez l\'administrateur.',
                'errors'  => null
            ], 403);
        }

        // CAS 1 : Compte non encore activé (première connexion)
        if (!$user->compte_active) {
            // Vérifier le mot de passe d'activation
            if (!Hash::check($request->password, $user->mot_de_passe_hash)) {
                // Tentative échouée : régénérer le mdp d'activation
                $newMdp = $this->generateActivationPassword();
                $newAttempts = $user->tentatives_activation + 1;

                $user->update([
                    'mdp_activation'        => $newMdp,
                    'mot_de_passe_hash'     => Hash::make($newMdp),
                    'tentatives_activation' => $newAttempts,
                ]);

                // Si 3 tentatives échouées : bloquer le compte
                if ($newAttempts >= self::MAX_ACTIVATION_ATTEMPTS) {
                    $user->update(['compte_bloque' => true]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Compte bloqué après 3 tentatives échouées. Contactez l\'administrateur.',
                        'errors'  => null
                    ], 423);
                }

                return response()->json([
                    'success' => false,
                    'message' => "Mot de passe d'activation incorrect. Le mot de passe a été régénéré. Tentative {$newAttempts}/3. Contactez l'administrateur pour le nouveau mot de passe.",
                    'errors'  => null
                ], 401);
            }

            // Mot de passe d'activation correct : retourner un token temporaire pour la définition du mdp personnel
            return response()->json([
                'success'         => true,
                'first_login'     => true,
                'user_id'         => $user->id,
                'temp_token'      => $user->createToken('first_login', ['first-login'])->plainTextToken,
                'message'         => 'Première connexion. Veuillez définir votre mot de passe personnel.',
                'errors'          => null
            ]);
        }

        // CAS 2 : Compte activé (connexion normale)

        // Vérifier verrouillage temporaire (5 tentatives)
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
                $user->update(['tentatives_echec' => 0]);
            }
        }

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

        // Connexion réussie
        $user->update([
            'tentatives_echec'        => 0,
            'date_derniere_connexion' => now(),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => [
                'id'        => $user->id,
                'nom'       => $user->nom,
                'prenom'    => $user->prenom,
                'email'     => $user->email,
                'statut'    => $user->statut,
                'telephone' => $user->telephone,
                'role'      => $userRole->libelle,
                'role_code' => $userRole->name,
            ],
            'message' => 'Connexion réussie',
            'errors'  => null
        ]);
    }

    /**
     * POST /set-password — Définir son mot de passe personnel (première connexion)
     */
    public function setPersonalPassword(Request $request)
    {
        $request->validate([
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $user = $request->user();

        if ($user->compte_active) {
            return response()->json([
                'success' => false,
                'message' => 'Ce compte est déjà activé.',
                'errors'  => null
            ], 422);
        }

        $user->update([
            'mot_de_passe_hash'     => Hash::make($request->password),
            'compte_active'         => true,
            'mdp_activation'        => null,
            'tentatives_activation' => 0,
        ]);

        // Supprimer le token temporaire
        $user->tokens()->delete();

        // Créer un vrai token de session
        $token = $user->createToken('auth_token')->plainTextToken;
        $role = $user->roles->first();

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => [
                'id'        => $user->id,
                'nom'       => $user->nom,
                'prenom'    => $user->prenom,
                'email'     => $user->email,
                'statut'    => $user->statut,
                'role'      => $role ? $role->libelle : null,
                'role_code' => $role ? $role->name : null,
            ],
            'message' => 'Mot de passe défini avec succès. Compte activé.',
            'errors'  => null
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
}