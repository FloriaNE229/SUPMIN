<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Modules\Entities\Models\Entity;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    const ACTIVATION_VALIDITY_DAYS = 3;

    private function generateActivationPassword(): string
    {
        return 'Supmin#' . strtoupper(Str::random(7));
    }

    public function index(Request $request)
    {
        $query = User::with(['roles', 'entity']);

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

        if ($request->has('entity_id')) {
            $query->where('entity_id', $request->entity_id);
        }

        $users = $query->latest()->get()->map(function ($user) {
            $role = $user->roles->first();
            return [
                'id'                       => $user->id,
                'nom'                      => $user->nom,
                'prenom'                   => $user->prenom,
                'email'                    => $user->email,
                'telephone'                => $user->telephone,
                'statut'                   => $user->statut,
                'role'                     => $role ? $role->libelle : null,
                'role_code'                => $role ? $role->name : null,
                'entity_id'                => $user->entity_id,
                'entite'                   => $user->entity ? $user->entity->denomination : null,
                'entite_sigle'             => $user->entity ? $user->entity->sigle : null,
                'compte_active'            => (bool) $user->compte_active,
                'tentatives_activation'    => $user->tentatives_activation,
                'compte_bloque'            => (bool) $user->compte_bloque,
                'mdp_activation_expire_at' => $user->mdp_activation_expire_at,
                'activation_expiree'       => $user->isActivationPasswordExpired(),
                'created_at'               => $user->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $users,
            'message' => 'Liste des utilisateurs',
            'errors'  => null
        ]);
    }

    public function show(User $user)
    {
        $user->load(['roles', 'entity']);
        $role = $user->roles->first();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                       => $user->id,
                'nom'                      => $user->nom,
                'prenom'                   => $user->prenom,
                'email'                    => $user->email,
                'telephone'                => $user->telephone,
                'statut'                   => $user->statut,
                'role'                     => $role ? $role->libelle : null,
                'role_code'                => $role ? $role->name : null,
                'entity_id'                => $user->entity_id,
                'entite'                   => $user->entity ? $user->entity->denomination : null,
                'compte_active'            => (bool) $user->compte_active,
                'tentatives_activation'    => $user->tentatives_activation,
                'compte_bloque'            => (bool) $user->compte_bloque,
                'mdp_activation_expire_at' => $user->mdp_activation_expire_at,
                'activation_expiree'       => $user->isActivationPasswordExpired(),
            ],
            'message' => 'Utilisateur trouvé',
            'errors'  => null
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom'       => 'required|string|max:100',
            'prenom'    => 'required|string|max:100',
            'email'     => 'required|email|unique:users,email',
            'telephone' => 'nullable|string|max:20',
            'role'      => 'required|string|exists:roles,name',
            'entity_id' => 'nullable|uuid|exists:entites,id',
        ]);

        $role = $request->role;
        $entityId = $request->entity_id;

        // RG-USR-003
        if (in_array($role, ['agent', 'responsable_entite']) && !$entityId) {
            return response()->json([
                'success' => false,
                'message' => 'Les agents et responsables d\'entité doivent être rattachés à une entité.',
                'errors'  => null
            ], 422);
        }

        // RG-ENT-002
        if ($role === 'responsable_entite' && $entityId) {
            $entity = Entity::find($entityId);
            if ($entity && $entity->responsable_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette entité a déjà un responsable.',
                    'errors'  => null
                ], 422);
            }
        }

        $mdpActivation = $this->generateActivationPassword();
        $expireAt = now()->addDays(self::ACTIVATION_VALIDITY_DAYS);

        $user = User::create([
            'id'                       => (string) Str::uuid(),
            'nom'                      => $request->nom,
            'prenom'                   => $request->prenom,
            'email'                    => $request->email,
            'mot_de_passe_hash'        => Hash::make($mdpActivation),
            'telephone'                => $request->telephone,
            'entity_id'                => $entityId,
            'statut'                   => 'actif',
            'tentatives_echec'         => 0,
            'compte_active'            => false,
            'mdp_activation'           => $mdpActivation,
            'mdp_activation_expire_at' => $expireAt,
            'tentatives_activation'    => 0,
            'compte_bloque'            => false,
        ]);

        $user->assignRole($role);

        if ($role === 'responsable_entite' && $entityId) {
            Entity::where('id', $entityId)->update(['responsable_id' => $user->id]);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'user'                     => $user->load(['roles', 'entity']),
                'mdp_activation'           => $mdpActivation,
                'mdp_activation_expire_at' => $expireAt,
            ],
            'message' => 'Utilisateur créé. Mot de passe d\'activation généré (valide 3 jours).',
            'errors'  => null
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'nom'       => 'sometimes|string|max:100',
            'prenom'    => 'sometimes|string|max:100',
            'email'     => 'sometimes|email|unique:users,email,' . $user->id,
            'telephone' => 'nullable|string|max:20',
            'role'      => 'sometimes|string|exists:roles,name',
            'entity_id' => 'nullable|uuid|exists:entites,id',
        ]);

        $oldRole = $user->roles->first()?->name;
        $oldEntityId = $user->entity_id;

        $user->update($request->only(['nom', 'prenom', 'email', 'telephone', 'entity_id']));

        if ($request->has('role')) {
            $user->syncRoles([$request->role]);
        }

        $newRole = $request->role ?? $oldRole;
        $newEntityId = $request->entity_id ?? $oldEntityId;

        if ($newRole === 'responsable_entite' && $newEntityId) {
            if ($oldEntityId && $oldEntityId !== $newEntityId) {
                Entity::where('responsable_id', $user->id)->update(['responsable_id' => null]);
            }
            Entity::where('id', $newEntityId)->update(['responsable_id' => $user->id]);
        }

        if ($oldRole === 'responsable_entite' && $newRole !== 'responsable_entite') {
            Entity::where('responsable_id', $user->id)->update(['responsable_id' => null]);
        }

        return response()->json([
            'success' => true,
            'data'    => $user->load(['roles', 'entity']),
            'message' => 'Utilisateur mis à jour',
            'errors'  => null
        ]);
    }

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
                'mdp_activation'           => $user->mdp_activation,
                'mdp_activation_expire_at' => $user->mdp_activation_expire_at,
                'activation_expiree'       => $user->isActivationPasswordExpired(),
                'tentatives_activation'    => $user->tentatives_activation,
                'compte_bloque'            => (bool) $user->compte_bloque,
            ],
            'message' => 'Mot de passe d\'activation',
            'errors'  => null
        ]);
    }

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
        $expireAt = now()->addDays(self::ACTIVATION_VALIDITY_DAYS);

        $user->update([
            'mdp_activation'           => $newMdp,
            'mdp_activation_expire_at' => $expireAt,
            'mot_de_passe_hash'        => Hash::make($newMdp),
            'tentatives_activation'    => 0, // Reset des tentatives
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'mdp_activation'           => $newMdp,
                'mdp_activation_expire_at' => $expireAt,
            ],
            'message' => 'Nouveau mot de passe d\'activation généré (valide 3 jours)',
            'errors'  => null
        ]);
    }

    public function unblock(User $user)
    {
        $newMdp = $this->generateActivationPassword();
        $expireAt = now()->addDays(self::ACTIVATION_VALIDITY_DAYS);

        $user->update([
            'compte_bloque'            => false,
            'tentatives_activation'    => 0,
            'mdp_activation'           => $newMdp,
            'mdp_activation_expire_at' => $expireAt,
            'mot_de_passe_hash'        => Hash::make($newMdp),
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'mdp_activation'           => $newMdp,
                'mdp_activation_expire_at' => $expireAt,
            ],
            'message' => 'Compte débloqué. Nouveau mot de passe d\'activation généré.',
            'errors'  => null
        ]);
    }

    public function suspend(User $user)
    {
        $user->update(['statut' => 'suspendu']);
        return response()->json(['success' => true, 'data' => null, 'message' => 'Utilisateur suspendu', 'errors' => null]);
    }

    public function activate(User $user)
    {
        $user->update(['statut' => 'actif', 'tentatives_echec' => 0]);
        return response()->json(['success' => true, 'data' => null, 'message' => 'Utilisateur réactivé', 'errors' => null]);
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer votre propre compte',
                'errors'  => null
            ], 403);
        }

        Entity::where('responsable_id', $user->id)->update(['responsable_id' => null]);
        $user->delete();

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'Utilisateur supprimé',
            'errors'  => null
        ]);
    }
}