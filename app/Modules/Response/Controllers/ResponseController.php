<?php

namespace App\Modules\Response\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Response\Models\Response;
use Illuminate\Support\Str;

class ResponseController extends Controller
{
    /**
     * POST /responses
     * RG-FOR-007 : Données horodatées + géolocalisées
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'mission_id'      => 'required|exists:missions,id',
            'question_id'     => 'required|exists:questions,id',
            'valeur_texte'    => 'nullable|string',
            'valeur_json'     => 'nullable|array',
            'fichiers_joints' => 'nullable|array',
            'latitude'        => 'nullable|numeric|between:-90,90',
            'longitude'       => 'nullable|numeric|between:-180,180',
            'mode_collecte'   => 'nullable|in:online,offline',
        ]);

        $response = Response::updateOrCreate(
            [
                // Anti doublon
                'mission_id'  => $validated['mission_id'],
                'question_id' => $validated['question_id'],
                'agent_id'    => $request->user()->id,
            ],
            [
                'id'              => Str::uuid(),
                'valeur_texte'    => $validated['valeur_texte'] ?? null,
                'valeur_json'     => $validated['valeur_json'] ?? null,
                'fichiers_joints' => $validated['fichiers_joints'] ?? null,
                'latitude'        => $validated['latitude'] ?? null,
                'longitude'       => $validated['longitude'] ?? null,
                'submitted_at'    => now(),
                'mode_collecte'   => $validated['mode_collecte'] ?? 'online',
            ]
        );

        return response()->json([
            'success' => true,
            'data'    => $response,
            'message' => 'Réponse enregistrée',
            'errors'  => null,
        ]);
    }

    /**
     * GET /responses?mission_id=...
     * Récupère les réponses de l'utilisateur connecté pour une mission
     */
    public function index(Request $request)
    {
        $request->validate([
            'mission_id' => 'required|exists:missions,id',
        ]);

        $responses = Response::where('mission_id', $request->mission_id)
            ->where('agent_id', $request->user()->id)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $responses,
            'message' => 'Réponses',
            'errors'  => null,
        ]);
    }
}