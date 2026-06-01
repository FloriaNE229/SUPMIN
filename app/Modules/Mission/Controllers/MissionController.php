<?php

namespace App\Modules\Mission\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Mission\Models\Mission;
use App\Modules\Mission\Services\MissionService;
use App\Modules\Mission\Requests\CreateMissionRequest;
use App\Modules\Mission\Requests\UpdateMissionRequest;
use App\Modules\Recommendation\Models\Recommendation;

class MissionController extends Controller
{
    public function __construct(private MissionService $service) {}

    /**
     * GET /missions
     */
    public function index(Request $request)
    {
        $query = Mission::with(['entity', 'coordinateur', 'agents']);

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('entity_id')) {
            $query->where('entity_id', $request->entity_id);
        }

        $missions = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data'    => $missions,
            'message' => 'Liste des missions',
            'errors'  => null
        ]);
    }

    /**
     * GET /missions/{id}
     */
    public function show(Mission $mission)
    {
        $mission->load(['entity', 'coordinateur', 'agents', 'forms', 'recommendations']);

        return response()->json([
            'success' => true,
            'data'    => $mission,
            'message' => 'Détail de la mission',
            'errors'  => null
        ]);
    }

    /**
     * POST /missions
     */
    public function store(CreateMissionRequest $request)
    {
        $mission = $this->service->create($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'data'    => $mission,
            'message' => 'Mission créée',
            'errors'  => null
        ], 201);
    }

    /**
     * PUT /missions/{id}
     */
    public function update(UpdateMissionRequest $request, Mission $mission)
    {
        $mission = $this->service->update($mission, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'data'    => $mission,
            'message' => 'Mission mise à jour',
            'errors'  => null
        ]);
    }

    /**
     * PATCH /missions/{id}/validate
     * RG-MIS-001 : Validation par coordinateur
     * RG-MIS-003 : Au moins un formulaire associé
     */
    public function validateMission(Mission $mission)
    {
        if ($mission->statut !== 'planifiée') {
            return response()->json([
                'success' => false,
                'message' => 'Seule une mission planifiée peut être validée',
                'errors'  => null
            ], 422);
        }

        // RG-MIS-003 : vérifier qu'au moins un formulaire est associé
        if ($mission->forms()->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de valider : aucun formulaire n\'est associé à cette mission (RG-MIS-003)',
                'errors'  => ['forms' => 'Au moins un formulaire doit être créé et associé à la mission']
            ], 422);
        }

        $mission->update(['statut' => 'en_cours']);

        // Log
        $mission->logs()->create([
            'action'      => 'validated',
            'description' => 'Mission validée et démarrée',
            'user_id'     => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $mission->fresh(),
            'message' => 'Mission validée et démarrée',
            'errors'  => null
        ]);
    }

    /**
     * PATCH /missions/{id}/close
     * RG-MIS-005 : Clôture de mission
     */
    public function close(Request $request, Mission $mission)
    {
        $request->validate([
            'commentaire' => 'nullable|string',
        ]);

        if ($mission->statut !== 'en_cours') {
            return response()->json([
                'success' => false,
                'message' => 'Seule une mission en cours peut être clôturée',
                'errors'  => null
            ], 422);
        }

        $mission->update([
            'statut'             => 'clôturée',
            'date_fin_effective' => now(),
        ]);

        // Log
        $mission->logs()->create([
            'action'      => 'closed',
            'description' => $request->commentaire ?? 'Mission clôturée',
            'user_id'     => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $mission->fresh(),
            'message' => 'Mission clôturée avec succès',
            'errors'  => null
        ]);
    }

    /**
     * GET /missions/{id}/unresolved-recommendations
     * RG-REC-004 : Recommandations non clôturées des missions précédentes
     */
    public function unresolvedRecommendations(Mission $mission)
    {
        $entityId = $mission->entity_id;

        $unresolved = Recommendation::whereHas('mission', function ($q) use ($entityId, $mission) {
            $q->where('entity_id', $entityId)
              ->where('id', '!=', $mission->id)
              ->where('statut', 'clôturée');
        })
        ->whereNotIn('statut', ['clôturée', 'non_mise_en_oeuvre'])
        ->with(['mission', 'responsable'])
        ->get();

        return response()->json([
            'success' => true,
            'data'    => $unresolved,
            'message' => 'Recommandations non clôturées des missions précédentes',
            'errors'  => null
        ]);
    }

    /**
     * GET /missions/{id}/pdf
     */
    public function pdf(Mission $mission)
    {
        if (!$mission->pdf_path) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun PDF disponible'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => ['url' => asset('storage/' . $mission->pdf_path)],
            'message' => 'PDF disponible',
            'errors'  => null
        ]);
    }
}