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
    const ACTIVATION_VALIDITY_DAYS = 3;

    private function generateActivationPassword(): string
    {
        return 'Supmin#' . strtoupper(Str::random(7));
    }

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

        $userRole = $user->roles->first();
        if (!$userRole || $userRole->libelle !== $request->role) {
            return response()->json([
                'success' => false,
                'message' => 'Le rôle sélectionné ne correspond pas à votre compte.',
                'errors'  => null
            ], 403);
        }

        if ($user->compte_bloque) {
            return response()->json([
                'success' => false,
                'message' => 'Compte bloqué après 3 tentatives échouées. Contactez l\'administrateur.',
                'errors'  => null
            ], 423);
        }

        if ($user->statut === 'suspendu') {
            return response()->json([
                'success' => false,
                'message' => 'Compte suspendu. Contactez l\'administrateur.',
                'errors'  => null
            ], 403);
        }

        if (!$user->compte_active) {

            if ($user->isActivationPasswordExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre mot de passe d\'activation a expiré (validité : 3 jours). Contactez l\'administrateur pour en obtenir un nouveau.',
                    'errors'  => null
                ], 401);
            }

            if (!Hash::check($request->password, $user->mot_de_passe_hash)) {
                $newMdp = $this->generateActivationPassword();
                $newExpireAt = now()->addDays(self::ACTIVATION_VALIDITY_DAYS);
                $newAttempts = $user->tentatives_activation + 1;

                $user->update([
                    'mdp_activation'           => $newMdp,
                    'mdp_activation_expire_at' => $newExpireAt,
                    'mot_de_passe_hash'        => Hash::make($newMdp),
                    'tentatives_activation'    => $newAttempts,
                ]);

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

            return response()->json([
                'success'     => true,
                'first_login' => true,
                'user_id'     => $user->id,
                'temp_token'  => $user->createToken('first_login', ['first-login'])->plainTextToken,
                'message'     => 'Première connexion. Veuillez définir votre mot de passe personnel.',
                'errors'      => null
            ]);
        }

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
                'entity_id' => $user->entity_id,
                'role'      => $userRole->libelle,
                'role_code' => $userRole->name,
            ],
            'message' => 'Connexion réussie',
            'errors'  => null
        ]);
    }

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
            'mot_de_passe_hash'        => Hash::make($request->password),
            'compte_active'            => true,
            'mdp_activation'           => null,
            'mdp_activation_expire_at' => null,
            'tentatives_activation'    => 0,
        ]);

        $user->tokens()->delete();
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
                'entity_id' => $user->entity_id,
                'role'      => $role ? $role->libelle : null,
                'role_code' => $role ? $role->name : null,
            ],
            'message' => 'Mot de passe défini avec succès. Compte activé.',
            'errors'  => null
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnecté',
            'errors'  => null
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load(['roles', 'entity']);
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
                'entity_id' => $user->entity_id,
                'entite'    => $user->entity ? $user->entity->denomination : null,
                'role'      => $role ? $role->libelle : null,
                'role_code' => $role ? $role->name : null,
            ],
            'message' => 'Utilisateur authentifié',
            'errors'  => null
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun utilisateur trouvé avec cet email.',
                'errors'  => null,
            ], 404);
        }

        if ($user->compte_bloque) {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte est bloqué. Contactez l\'administrateur.',
                'errors'  => null,
            ], 403);
        }

        $newActivationPassword = $this->generateActivationPassword();

        $user->update([
            'mdp_activation'           => Hash::make($newActivationPassword),
            'mdp_activation_expire_at' => now()->addDays(self::ACTIVATION_VALIDITY_DAYS),
            'compte_active'            => false,
            'tentatives_activation'    => 0,
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'message'            => 'Un mot de passe temporaire vous a été envoyé par email.',
                'temporary_password' => $newActivationPassword,
            ],
            'message' => 'Mot de passe temporaire généré et valide pendant 3 jours.',
            'errors'  => null,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'                    => 'required|email|exists:users,email',
            'temporary_password'       => 'required|string',
            'new_password'             => 'required|string|min:8|confirmed',
            'new_password_confirmation' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->temporary_password, $user->mdp_activation)) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe temporaire incorrect.',
                'errors'  => null,
            ], 401);
        }

        if ($user->isActivationPasswordExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Le mot de passe temporaire a expiré. Demandez-en un nouveau.',
                'errors'  => null,
            ], 401);
        }

        $user->update([
            'mot_de_passe_hash'        => Hash::make($request->new_password),
            'compte_active'            => true,
            'mdp_activation'           => null,
            'mdp_activation_expire_at' => null,
            'tentatives_activation'    => 0,
        ]);

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'Mot de passe réinitialisé avec succès. Vous pouvez vous connecter.',
            'errors'  => null,
        ]);
    }
    public function updateProfile(Request $request)
    {
        $user = $request->user();
    
        $request->validate([
            'nom'       => 'sometimes|string|max:100',
            'prenom'    => 'sometimes|string|max:100',
            'telephone' => 'sometimes|nullable|string|max:20',
            'email'     => 'sometimes|email|unique:users,email,' . $user->id,
        ]);
    
        $user->update($request->only(['nom', 'prenom', 'telephone', 'email']));
    
        $user->load(['roles', 'entity']);
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
                'entity_id' => $user->entity_id,
                'entite'    => $user->entity ? $user->entity->denomination : null,
                'role'      => $role ? $role->libelle : null,
                'role_code' => $role ? $role->name : null,
            ],
            'message' => 'Profil mis à jour avec succès.',
            'errors'  => null,
        ]);
    }
    

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password'      => 'required|string',
            'new_password'          => 'required|string|min:8|confirmed',
            'new_password_confirmation' => 'required',
        ]);
    
        $user = $request->user();
    
        if (!Hash::check($request->current_password, $user->mot_de_passe_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Le mot de passe actuel est incorrect.',
                'errors'  => null,
            ], 401);
        }
    
        $user->update([
            'mot_de_passe_hash' => Hash::make($request->new_password),
        ]);
    
        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'Mot de passe modifié avec succès.',
            'errors'  => null,
        ]);
    }
}