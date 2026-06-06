<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Génère un mot de passe d'activation unique
     */
    private function generateActivationPassword(): string
    {
        return 'Supmin#' . strtoupper(Str::random(7));
    }

    /**
     * GET /users — Liste tous les utilisateurs
     */
    public function index(Request $request)
    {
        $query = User::with('roles');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        $users = $query->latest()->get()->map(function ($user) {
            $role = $user->roles->first();
            return [
                'id'                    => $user->id,
                'nom'                   => $user->nom,
                'prenom'                => $user->prenom,
                'email'                 => $user->email,
                'telephone'             => $user->telephone,
                'statut'                => $user->statut,
                'role'                  => $role ? $role->libelle : null,
                'role_code'             => $role ? $role->name : null,
                'compte_active'         => (bool) $user->compte_active,
                'tentatives_activation' => $user->tentatives_activation,
                'compte_bloque'         => (bool) $user->compte_bloque,
                'twofa'                 => false,
                'created_at'            => $user->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $users,
            'message' => 'Liste des utilisateurs',
            'errors'  => null
        ]);
    }

    /**
     * GET /users/{id}
     */
    public function show(User $user)
    {
        $role = $user->roles->first();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                    => $user->id,
                'nom'                   => $user->nom,
                'prenom'                => $user->prenom,
                'email'                 => $user->email,
                'telephone'             => $user->telephone,
                'statut'                => $user->statut,
                'role'                  => $role ? $role->libelle : null,
                'role_code'             => $role ? $role->name : null,
                'compte_active'         => (bool) $user->compte_active,
                'tentatives_activation' => $user->tentatives_activation,
                'compte_bloque'         => (bool) $user->compte_bloque,
            ],
            'message' => 'Utilisateur trouvé',
            'errors'  => null
        ]);
    }

    /**
     * POST /users — Créer un utilisateur (admin)
     * Génère automatiquement un mot de passe d'activation unique
     */
    public function store(Request $request)
    {
        $request->validate([
            'nom'       => 'required|string|max:100',
            'prenom'    => 'required|string|max:100',
            'email'     => 'required|email|unique:users,email',
            'telephone' => 'nullable|string|max:20',
            'role'      => 'required|string|exists:roles,name',
        ]);

        // Génération du mot de passe d'activation
        $mdpActivation = $this->generateActivationPassword();

        $user = User::create([
            'id'                    => (string) Str::uuid(),
            'nom'                   => $request->nom,
            'prenom'                => $request->prenom,
            'email'                 => $request->email,
            'mot_de_passe_hash'     => Hash::make($mdpActivation),
            'telephone'             => $request->telephone,
            'statut'                => 'actif',
            'tentatives_echec'      => 0,
            'compte_active'         => false,
            'mdp_activation'        => $mdpActivation, // En clair pour que l'admin le voie
            'tentatives_activation' => 0,
            'compte_bloque'         => false,
        ]);

        $user->assignRole($request->role);

        return response()->json([
            'success' => true,
            'data'    => [
                'user'           => $user->load('roles'),
                'mdp_activation' => $mdpActivation,
            ],
            'message' => 'Utilisateur créé. Mot de passe d\'activation généré.',
            'errors'  => null
        ], 201);
    }

    /**
     * GET /users/{id}/activation-password — Voir le mdp d'activation actuel
     */
    public function getActivationPassword(User $user)
    {
        if ($user->compte_active) {
            return response()->json([
                'success' => false,
                'message' => 'Ce compte est déjà activé.',
                'errors'  => null
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'mdp_activation'        => $user->mdp_activation,
                'tentatives_activation' => $user->tentatives_activation,
                'compte_bloque'         => (bool) $user->compte_bloque,
            ],
            'message' => 'Mot de passe d\'activation',
            'errors'  => null
        ]);
    }

    /**
     * POST /users/{id}/regenerate-activation — Régénérer le mdp d'activation
     */
    public function regenerateActivationPassword(User $user)
    {
        if ($user->compte_active) {
            return response()->json([
                'success' => false,
                'message' => 'Ce compte est déjà activé.',
                'errors'  => null
            ], 422);
        }

        $newMdp = $this->generateActivationPassword();

        $user->update([
            'mdp_activation'    => $newMdp,
            'mot_de_passe_hash' => Hash::make($newMdp),
        ]);

        return response()->json([
            'success' => true,
            'data'    => ['mdp_activation' => $newMdp],
            'message' => 'Nouveau mot de passe d\'activation généré',
            'errors'  => null
        ]);
    }

    /**
     * POST /users/{id}/unblock — Débloquer un compte après 3 tentatives échouées
     */
    public function unblock(User $user)
    {
        $newMdp = $this->generateActivationPassword();

        $user->update([
            'compte_bloque'         => false,
            'tentatives_activation' => 0,
            'mdp_activation'        => $newMdp,
            'mot_de_passe_hash'     => Hash::make($newMdp),
        ]);

        return response()->json([
            'success' => true,
            'data'    => ['mdp_activation' => $newMdp],
            'message' => 'Compte débloqué. Nouveau mot de passe d\'activation généré.',
            'errors'  => null
        ]);
    }

    /**
     * PUT /users/{id}
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'nom'       => 'sometimes|string|max:100',
            'prenom'    => 'sometimes|string|max:100',
            'email'     => 'sometimes|email|unique:users,email,' . $user->id,
            'telephone' => 'nullable|string|max:20',
            'role'      => 'sometimes|string|exists:roles,name',
        ]);

        $user->update($request->only(['nom', 'prenom', 'email', 'telephone']));

        if ($request->has('role')) {
            $user->syncRoles([$request->role]);
        }

        return response()->json([
            'success' => true,
            'data'    => $user->load('roles'),
            'message' => 'Utilisateur mis à jour',
            'errors'  => null
        ]);
    }

    /**
     * PATCH /users/{id}/suspend
     */
    public function suspend(User $user)
    {
        $user->update(['statut' => 'suspendu']);

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'Utilisateur suspendu',
            'errors'  => null
        ]);
    }

    /**
     * PATCH /users/{id}/activate
     */
    public function activate(User $user)
    {
        $user->update([
            'statut'           => 'actif',
            'tentatives_echec' => 0,
        ]);

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'Utilisateur réactivé',
            'errors'  => null
        ]);
    }

    /**
     * DELETE /users/{id}
     */
    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer votre propre compte',
                'errors'  => null
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'Utilisateur supprimé',
            'errors'  => null
        ]);
    }
}