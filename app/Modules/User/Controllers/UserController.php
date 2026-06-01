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
                'id'        => $user->id,
                'nom'       => $user->nom,
                'prenom'    => $user->prenom,
                'email'     => $user->email,
                'telephone' => $user->telephone,
                'statut'    => $user->statut,
                'role'      => $role ? $role->libelle : null,
                'role_code' => $role ? $role->name : null,
                'twofa'     => false,
                'created_at'=> $user->created_at,
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
                'id'        => $user->id,
                'nom'       => $user->nom,
                'prenom'    => $user->prenom,
                'email'     => $user->email,
                'telephone' => $user->telephone,
                'statut'    => $user->statut,
                'role'      => $role ? $role->libelle : null,
                'role_code' => $role ? $role->name : null,
            ],
            'message' => 'Utilisateur trouvé',
            'errors'  => null
        ]);
    }

    /**
     * POST /users — Créer un utilisateur (admin uniquement - RG-USR-002)
     */
    public function store(Request $request)
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
            'id'                => (string) Str::uuid(),
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
            'message' => 'Utilisateur créé avec succès',
            'errors'  => null
        ], 201);
    }

    /**
     * PUT /users/{id} — Modifier un utilisateur
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
     * PATCH /users/{id}/suspend — Suspendre un utilisateur
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
     * PATCH /users/{id}/activate — Réactiver un utilisateur
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
     * DELETE /users/{id} — Supprimer un utilisateur
     */
    public function destroy(User $user)
    {
        // Empêcher la suppression de son propre compte
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

    /**
     * GET /users/suspended — Vérifier comptes inactifs 90 jours (RG-USR-004)
     */
    public function checkInactive()
    {
        $inactive = User::where('statut', 'actif')
            ->where(function ($q) {
                $q->where('date_derniere_connexion', '<', now()->subDays(90))
                  ->orWhereNull('date_derniere_connexion');
            })
            ->where('created_at', '<', now()->subDays(90))
            ->get();

        foreach ($inactive as $user) {
            $user->update(['statut' => 'suspendu']);
        }

        return response()->json([
            'success' => true,
            'data'    => ['suspended_count' => $inactive->count()],
            'message' => "{$inactive->count()} compte(s) suspendu(s) pour inactivité",
            'errors'  => null
        ]);
    }
}
