<?php

namespace App\Modules\Recommendation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Modules\Recommendation\Models\Recommendation;
use App\Modules\Recommendation\Models\RecommendationTracking;
use App\Models\User;
use App\Notifications\RecommendationCreatedNotification;
use App\Notifications\RecommendationTransmittedNotification;
use App\Notifications\RecommendationStatusChangedNotification;
use App\Notifications\CriticalAlertNotification;

class RecommendationController extends Controller
{
    public function index(Request $request)
    {
        $query = Recommendation::with(['mission.entity', 'responsable', 'createdBy']);

        if ($request->has('mission_id')) $query->where('mission_id', $request->mission_id);
        if ($request->has('statut'))     $query->where('statut', $request->statut);
        if ($request->has('priorite'))   $query->where('priorite', $request->priorite);
        if ($request->has('responsable_id')) $query->where('responsable_id', $request->responsable_id);

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

    public function show(Recommendation $recommendation)
    {
        $recommendation->load(['mission.entity', 'responsable', 'createdBy']);

        $trackings = RecommendationTracking::where('recommandation_id', $recommendation->id)
            ->with('updatedBy')
            ->orderBy('created_at', 'asc')
            ->get();

        $data = $recommendation->toArray();
        $data['trackings'] = $trackings;

        return response()->json([
            'success' => true,
            'data'    => $data,
            'message' => 'Recommandation trouvée',
            'errors'  => null
        ]);
    }

    /**
     * POST /recommendations
     * Crée la reco + notifie le responsable
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'mission_id'        => 'required|exists:missions,id',
                'question_id'       => 'nullable|exists:questions,id',
                'intitule'          => 'required|string|max:255',
                'description'       => 'required|string',
                'priorite'          => 'required|in:critique,majeur,mineur',
                'responsable_id'    => 'required|exists:users,id',
                'delai_realisation' => 'required|date|after:today',
            ]);

            $year = date('Y');
            $count = Recommendation::whereYear('created_at', $year)->count() + 1;
            $reference = 'REC-' . $year . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

            $rec = Recommendation::create([
                ...$data,
                'id'         => (string) Str::uuid(),
                'reference'  => $reference,
                'statut'     => 'formulee',
                'nb_reports' => 0,
                'creee_par'  => $request->user()->id,
            ]);

            RecommendationTracking::create([
                'id'                => (string) Str::uuid(),
                'recommandation_id' => $rec->id,
                'ancien_statut'     => 'formulee',
                'nouveau_statut'    => 'formulee',
                'commentaire'       => 'Recommandation formulée',
                'updated_by'        => $request->user()->id,
                'created_at'        => now(),
            ]);

            // Notifier le responsable de la recommandation (non bloquant)
            try {
                $responsable = User::find($rec->responsable_id);
                if ($responsable) {
                    $responsable->notify(new RecommendationCreatedNotification($rec));
                }
            } catch (\Exception $e) {
                // Notification non bloquante
            }

            return response()->json([
                'success' => true,
                'data'    => $rec->load(['mission.entity', 'responsable']),
                'message' => 'Recommandation créée avec succès',
                'errors'  => null
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la recommandation',
                'errors'  => [
                    'detail' => $e->getMessage(),
                    'file'   => basename($e->getFile()),
                    'line'   => $e->getLine(),
                ]
            ], 500);
        }
    }

    /**
     * PATCH /recommendations/{id}/status
     */
    public function updateStatus(Request $request, Recommendation $recommendation)
    {
        try {
            $request->validate([
                'statut'      => 'required|in:formulee,transmise,en_cours,mise_en_oeuvre,cloturee,reportee,non_mise_en_oeuvre',
                'commentaire' => 'nullable|string',
                'preuves'     => 'nullable|array',
            ]);

            $user = $request->user();
            $userRole = $user->roles->first()?->name;

            // RG-REC-005 : Seul le coordinateur peut clôturer
            if (in_array($request->statut, ['cloturee', 'non_mise_en_oeuvre'])) {
                if (!in_array($userRole, ['admin', 'coordinateur'])) {
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

            // RG-REC-006 : report limité à 2
            $escalade = false;
            if ($request->statut === 'reportee') {
                if ($recommendation->nb_reports >= 2) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cette recommandation a déjà été reportée 2 fois. Elle doit être escaladée au niveau décisionnel (RG-REC-006)',
                        'errors'  => null
                    ], 422);
                }
                $recommendation->increment('nb_reports');
                if ($recommendation->nb_reports >= 2) {
                    $escalade = true;
                }
            }

            $ancienStatut = $recommendation->statut;
            $recommendation->update(['statut' => $request->statut]);

            RecommendationTracking::create([
                'id'                => (string) Str::uuid(),
                'recommandation_id' => $recommendation->id,
                'ancien_statut'     => $ancienStatut,
                'nouveau_statut'    => $request->statut,
                'commentaire'       => $request->commentaire,
                'preuves_jointes'   => $request->preuves ?: null,
                'updated_by'        => $user->id,
                'created_at'        => now(),
            ]);

            $recommendation->refresh();
            $recommendation->load(['mission.entity', 'responsable']);

            // Notifier le responsable du changement (non bloquant)
            try {
                if ($recommendation->responsable_id !== $user->id) {
                    $responsable = User::find($recommendation->responsable_id);
                    if ($responsable) {
                        $responsable->notify(new RecommendationStatusChangedNotification($recommendation, $ancienStatut, $request->statut));
                    }
                }
            } catch (\Exception $e) {
                // Non bloquant
            }

            // Notifier le créateur (coordinateur) si c'est le responsable qui met à jour (non bloquant)
            try {
                if ($recommendation->creee_par !== $user->id) {
                    $coordinateur = User::find($recommendation->creee_par);
                    if ($coordinateur) {
                        $coordinateur->notify(new RecommendationStatusChangedNotification($recommendation, $ancienStatut, $request->statut));
                    }
                }
            } catch (\Exception $e) {
                // Non bloquant
            }

            // RG-REC-006 : Escalade au Décideur Ministériel (non bloquant)
            if ($escalade) {
                try {
                    $decideurs = User::role('decideur')->get();
                    if ($decideurs->count() > 0) {
                        \Illuminate\Support\Facades\Notification::send(
                            $decideurs,
                            new CriticalAlertNotification($recommendation, 'Recommandation reportée 2 fois (RG-REC-006)')
                        );
                    }
                } catch (\Exception $e) {
                    // Non bloquant
                }
            }

            return response()->json([
                'success' => true,
                'data'    => $recommendation,
                'message' => 'Statut mis à jour',
                'errors'  => null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut',
                'errors'  => [
                    'detail' => $e->getMessage(),
                    'file'   => basename($e->getFile()),
                    'line'   => $e->getLine(),
                ]
            ], 500);
        }
    }

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
     * Validation par le validateur ou coordinateur
     */
    public function validateRec(Request $request, Recommendation $recommendation)
    {
        try {
            $user = $request->user();
            $userRole = $user->roles->first()?->name;

            if (!in_array($userRole, ['admin', 'validateur', 'coordinateur'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé',
                    'errors'  => null
                ], 403);
            }

            $ancienStatut = $recommendation->statut;

            $recommendation->update([
                'statut' => 'transmise',
            ]);

            RecommendationTracking::create([
                'id'                => (string) Str::uuid(),
                'recommandation_id' => $recommendation->id,
                'ancien_statut'     => $ancienStatut,
                'nouveau_statut'    => 'transmise',
                'commentaire'       => 'Recommandation validée et transmise au responsable',
                'updated_by'        => $user->id,
                'created_at'        => now(),
            ]);

            // Notifier le responsable (non bloquant)
            try {
                $responsable = User::find($recommendation->responsable_id);
                if ($responsable) {
                    $responsable->notify(new RecommendationTransmittedNotification($recommendation));
                }
            } catch (\Exception $e) {
                // Non bloquant
            }

            return response()->json([
                'success' => true,
                'data'    => $recommendation->fresh()->load(['mission.entity', 'responsable']),
                'message' => 'Recommandation validée et transmise',
                'errors'  => null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation',
                'errors'  => [
                    'detail' => $e->getMessage(),
                    'file'   => basename($e->getFile()),
                    'line'   => $e->getLine(),
                ]
            ], 500);
        }
    }

    public function requestRevision(Request $request, Recommendation $recommendation)
    {
        try {
            $request->validate(['commentaire' => 'required|string']);

            $ancienStatut = $recommendation->statut;
            $recommendation->update(['statut' => 'formulee']);

            RecommendationTracking::create([
                'id'                => (string) Str::uuid(),
                'recommandation_id' => $recommendation->id,
                'ancien_statut'     => $ancienStatut,
                'nouveau_statut'    => 'formulee',
                'commentaire'       => 'Révision demandée : ' . $request->commentaire,
                'updated_by'        => $request->user()->id,
                'created_at'        => now(),
            ]);

            return response()->json([
                'success' => true,
                'data'    => $recommendation->fresh(),
                'message' => 'Révision demandée',
                'errors'  => null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la demande de révision',
                'errors'  => [
                    'detail' => $e->getMessage(),
                    'file'   => basename($e->getFile()),
                    'line'   => $e->getLine(),
                ]
            ], 500);
        }
    }
}