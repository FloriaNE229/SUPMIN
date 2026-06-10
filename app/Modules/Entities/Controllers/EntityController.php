<?php

namespace App\Modules\Entities\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Entities\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EntityController extends Controller
{
    /**
     * GET /entities
     */
    public function index(Request $request)
    {
        $query = Entity::with(['responsable', 'parent']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('denomination', 'like', "%{$search}%")
                  ->orWhere('sigle', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->has('type_entite')) {
            $query->where('type_entite', $request->type_entite);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        $entities = $query->orderBy('denomination')->get();

        return response()->json([
            'success' => true,
            'data'    => $entities,
            'message' => 'Liste des entités',
            'errors'  => null
        ]);
    }

    /**
     * GET /entities/{entity}
     */
    public function show(Entity $entity)
    {
        $entity->load(['responsable', 'parent', 'enfants']);

        return response()->json([
            'success' => true,
            'data'    => $entity,
            'message' => 'Détail de l\'entité',
            'errors'  => null
        ]);
    }

    /**
     * POST /entities
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'              => 'required|string|max:20|unique:entites,code',
            'denomination'      => 'required|string|max:255',
            'sigle'             => 'nullable|string|max:50',
            'type_entite'       => 'required|in:structure_administrative,agence,programme',
            'localisation'      => 'nullable|string|max:255',
            'region'            => 'required|string|max:100',
            'responsable_id'    => 'nullable|uuid|exists:users,id',
            'statut'            => 'nullable|in:actif,suspendu,cloture',
            'date_creation'     => 'nullable|date',
            'entite_parente_id' => 'nullable|uuid|exists:entites,id',
        ]);

        // Valeurs par défaut
        $validated['id']            = (string) Str::uuid();
        $validated['localisation']  = $validated['localisation'] ?? $validated['region'];
        $validated['date_creation'] = $validated['date_creation'] ?? now();
        $validated['statut']        = $validated['statut'] ?? 'actif';

        $entity = Entity::create($validated);

        return response()->json([
            'success' => true,
            'data'    => $entity->load(['responsable', 'parent']),
            'message' => 'Entité créée',
            'errors'  => null
        ], 201);
    }

    /**
     * PUT /entities/{entity}
     */
    public function update(Request $request, Entity $entity)
    {
        $validated = $request->validate([
            'code'              => 'sometimes|string|max:20|unique:entites,code,' . $entity->id,
            'denomination'      => 'sometimes|string|max:255',
            'sigle'             => 'nullable|string|max:50',
            'type_entite'       => 'sometimes|in:structure_administrative,agence,programme',
            'localisation'      => 'nullable|string|max:255',
            'region'            => 'sometimes|string|max:100',
            'responsable_id'    => 'nullable|uuid|exists:users,id',
            'statut'            => 'sometimes|in:actif,suspendu,cloture',
            'date_creation'     => 'nullable|date',
            'entite_parente_id' => 'nullable|uuid|exists:entites,id',
        ]);

        $entity->update($validated);

        return response()->json([
            'success' => true,
            'data'    => $entity->load(['responsable', 'parent']),
            'message' => 'Entité mise à jour',
            'errors'  => null
        ]);
    }

    /**
     * DELETE /entities/{entity}
     */
    public function destroy(Entity $entity)
    {
        $entity->delete();

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'Entité supprimée',
            'errors'  => null
        ]);
    }
}