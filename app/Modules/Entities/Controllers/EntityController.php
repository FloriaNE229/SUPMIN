<?php

namespace App\Modules\Entities\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Entities\Models\Entity;
use Illuminate\Http\Request;

class EntityController extends Controller
{
    /**
     * GET /entities
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Entity::with([
                'responsable',
                'parent',
                'enfants'
            ])->get(),
            'message' => 'Liste des entités',
            'errors' => null
        ]);
    }

    /**
     * POST /entities
     */
    public function store(Request $request)
    {
        $validated = $request->validate([

            'code' => 'required|string|max:20|unique:entites,code',

            'denomination' => 'required|string|max:255',

            'sigle' => 'nullable|string|max:20',

            'type_entite' => 'required|in:structure_administrative,agence,programme',

            'localisation' => 'required|string|max:255',

            'region' => 'required|string|max:100',

            'responsable_id' => 'required|uuid|exists:users,id',

            'statut' => 'nullable|in:actif,suspendu,cloture',

            'date_creation' => 'required|date',

            'entite_parente_id' => 'nullable|uuid|exists:entites,id',
        ]);

        $entity = Entity::create($validated);

        return response()->json([
            'success' => true,
            'data' => $entity->load([
                'responsable',
                'parent'
            ]),
            'message' => 'Entité créée',
            'errors' => null
        ], 201);
    }

    /**
     * PUT /entities/{entity}
     */
    public function update(Request $request, Entity $entity)
    {
        $validated = $request->validate([

            'code' => 'sometimes|string|max:20|unique:entites,code,' . $entity->id,

            'denomination' => 'sometimes|string|max:255',

            'sigle' => 'nullable|string|max:20',

            'type_entite' => 'sometimes|in:structure_administrative,agence,programme',

            'localisation' => 'sometimes|string|max:255',

            'region' => 'sometimes|string|max:100',

            'responsable_id' => 'sometimes|uuid|exists:users,id',

            'statut' => 'sometimes|in:actif,suspendu,cloture',

            'date_creation' => 'sometimes|date',

            'entite_parente_id' => 'nullable|uuid|exists:entites,id',
        ]);

        $entity->update($validated);

        return response()->json([
            'success' => true,
            'data' => $entity->fresh()->load([
                'responsable',
                'parent'
            ]),
            'message' => 'Entité mise à jour',
            'errors' => null
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
            'data' => null,
            'message' => 'Entité supprimée',
            'errors' => null
        ]);
    }
}