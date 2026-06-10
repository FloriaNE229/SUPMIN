<?php

namespace App\Modules\Recommendation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Modules\Recommendation\Models\Recommendation;
use App\Modules\Recommendation\Models\RecommendationTracking;

class RecommendationController extends Controller
{
    /**
     * GET /recommendations
     */
    public function index(Request $request)
    {
        $query = Recommendation::with(['mission', 'responsable', 'createdBy']);

        if ($request->has('mission_id')) {
            $query->where('mission_id', $request->mission_id);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('priorite')) {
            $query->where('priorite', $request->priorite);
        }

        if ($request->has('responsable_id')) {
            $query->where('responsable_id', $request->responsable_id);
        }

        // Filtrer par entité via la mission
        if ($request->has('entity_id')) {
            $query->whereHas('mission', function ($q) use ($request) {
                $q->where('entity_id', $request->entity_id);
            });
        }

        $recs = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data'    => $recs,
            'message' => 'Liste des recommandations',
            'errors'  => null
        ]);
    }

    /**
     * GET /recommendations/{id}
     */
    public function show(Recommendation $recommendation)
    {
        $recommendation->load(['mission', 'responsable', 'createdBy', 'trackings.updatedBy']);

        return response()->json([
            'success' => true,
            'data'    => $recommendation,
            'message' => 'Recommandation trouvée',
            'errors'  => null
        ]);
    }

    /**
     * POST /recommendations
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'mission_id'       => 'required|exists:missions,id',
            'question_id'      => 'nullable|exists:questions,id',
            'reference'        => 'required|unique:recommendations,reference',
            'intitule'         => 'required|string|max:255',
            'description'      => 'required|string',
            'priorite'         => 'required|in:critique,majeur,mineur',
            'responsable_id'   => 'required|exists:users,id',
            'delai_realisation'=> 'required|date',
        ]);

        $rec = Recommendation::create([
            ...$data,
            'id'        => (string) Str::uuid(),
            'statut'    => 'formulée',
            'nb_reports'=> 0,
            'creee_par' => $request->user()->id,
        ]);

        // Enregistrer dans le tracking
        RecommendationTracking::create([
            'id'                  => (string) Str::uuid(),
            'recommandation_id'   => $rec->id,
            'ancien_statut'       => null,
            'nouveau_statut'      => 'formulée',
            'commentaire'         => 'Recommandation formulée',
            'updated_by'          => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'data'    => $rec->load(['mission', 'responsable']),
            'message' => 'Recommandation créée',
            'errors'  => null
        ], 201);
    }

    /**
     * PATCH /recommendations/{id}/status
     * Met à jour le statut + enregistre dans suivi_recommandations
     */
    public function updateStatus(Request $request, Recommendation $recommendation)
    {
        $request->validate([
            'statut'      => 'required|in:formulée,transmise,en_cours,mise_en_oeuvre,clôturée,reportée,non_mise_en_oeuvre',
            'commentaire' => 'nullable|string',
            'preuves'     => 'nullable|array',
        ]);

        // RG-REC-005 : Seul le coordinateur peut clôturer ou marquer non_mise_en_oeuvre
        if (in_array($request->statut, ['clôturée', 'non_mise_en_oeuvre'])) {
            if (!auth()->user()->hasRole(['admin', 'coordinateur'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seul le coordinateur peut clôturer ou marquer une recommandation comme non mise en œuvre (RG-REC-005)',
                    'errors'  => null
                ], 403);
            }

            if (!$request->commentaire) {
                return response()->json([
                    'success' => false,
                    'message' => 'Un commentaire de justification est obligatoire (RG-REC-005)',
                    'errors'  => ['commentaire' => 'Commentaire obligatoire']
                ], 422);
            }
        }

        // RG-REC-006 : Report limité à 2 fois
        if ($request->statut === 'reportée') {
            if ($recommendation->nb_reports >= 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette recommandation a déjà été reportée 2 fois. Elle doit être escaladée au niveau décisionnel (RG-REC-006)',
                    'errors'  => null
                ], 422);
            }

            $recommendation->increment('nb_reports');
        }

        $ancienStatut = $recommendation->statut;

        $recommendation->update(['statut' => $request->statut]);

        // Enregistrer dans suivi_recommandations
        RecommendationTracking::create([
            'id'                => (string) Str::uuid(),
            'recommandation_id' => $recommendation->id,
            'ancien_statut'     => $ancienStatut,
            'nouveau_statut'    => $request->statut,
            'commentaire'       => $request->commentaire,
            'preuves_jointes'   => $request->preuves ? json_encode($request->preuves) : null,
            'updated_by'        => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $recommendation->fresh()->load(['trackings.updatedBy']),
            'message' => 'Statut mis à jour',
            'errors'  => null
        ]);
    }

    /**
     * GET /recommendations/{id}/tracking
     * Historique des changements de statut (Table suivi_recommandations)
     */
    public function tracking(Recommendation $recommendation)
    {
        $history = RecommendationTracking::where('recommandation_id', $recommendation->id)
            ->with('updatedBy')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $history,
            'message' => 'Historique de la recommandation',
            'errors'  => null
        ]);
    }

    /**
     * POST /recommendations/{id}/validate
     * Validateur approuve une recommandation
     */
    public function validateRec(Request $request, Recommendation $recommendation)
    {
        if (!auth()->user()->hasRole(['admin', 'validateur', 'coordinateur'])) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé',
                'errors'  => null
            ], 403);
        }

        $recommendation->update([
            'statut'      => 'transmise',
            'validee_par' => auth()->id(),
        ]);

        RecommendationTracking::create([
            'id'                => (string) Str::uuid(),
            'recommandation_id' => $recommendation->id,
            'ancien_statut'     => 'formulée',
            'nouveau_statut'    => 'transmise',
            'commentaire'       => 'Recommandation validée et transmise',
            'updated_by'        => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $recommendation->fresh(),
            'message' => 'Recommandation validée et transmise',
            'errors'  => null
        ]);
    }

    /**
     * POST /recommendations/{id}/revision
     * Validateur demande une révision
     */
    public function requestRevision(Request $request, Recommendation $recommendation)
    {
        $request->validate([
            'commentaire' => 'required|string',
        ]);

        $ancienStatut = $recommendation->statut;
        $recommendation->update(['statut' => 'formulée']);

        RecommendationTracking::create([
            'id'                => (string) Str::uuid(),
            'recommandation_id' => $recommendation->id,
            'ancien_statut'     => $ancienStatut,
            'nouveau_statut'    => 'formulée',
            'commentaire'       => 'Révision demandée : ' . $request->commentaire,
            'updated_by'        => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'Révision demandée',
            'errors'  => null
        ]);
    }
}
