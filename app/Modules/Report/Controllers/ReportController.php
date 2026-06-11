<?php

namespace App\Modules\Report\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Modules\Report\Services\ReportService;
use App\Modules\Report\Services\ReportBuilderService;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $service,
        private ReportBuilderService $builder
    ) {}

    /**
     * GET /reports
     */
    public function index(Request $request)
    {
        $query = \App\Modules\Report\Models\Report::with(['mission.entity', 'validatedBy']);

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('entity_id')) {
            $query->whereHas('mission', function ($q) use ($request) {
                $q->where('entity_id', $request->entity_id);
            });
        }

        return response()->json([
            'success' => true,
            'data'    => $query->latest()->get(),
            'message' => 'Liste des rapports',
            'errors'  => null
        ]);
    }

    /**
     * GET /reports/{id}
     */
    public function show($id)
    {
        $report = \App\Modules\Report\Models\Report::with(['mission.entity', 'validatedBy'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $report,
            'message' => 'Rapport trouvé',
            'errors'  => null
        ]);
    }

    /**
     * GET /missions/{id}/report — Construire le rapport d'une mission
     */
    public function buildFromMission($id)
    {
        $report = $this->builder->build($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Mission introuvable',
                'errors'  => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $report,
            'message' => 'Rapport généré',
            'errors'  => null
        ]);
    }

    /**
     * PATCH /reports/{id}/validate — Validation du rapport (RG-RAP-002)
     * Coordinateur valide le rapport
     */
    public function validate(Request $request, $id)
    {
        $report = \App\Modules\Report\Models\Report::findOrFail($id);

        if ($report->statut === 'validé') {
            return response()->json([
                'success' => false,
                'message' => 'Ce rapport est déjà validé (RG-RAP-005)',
                'errors'  => null
            ], 422);
        }

        $report->update([
            'statut'          => 'validé',
            'validee_par'     => auth()->id(),
            'date_validation' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $report->fresh(),
            'message' => 'Rapport validé',
            'errors'  => null
        ]);
    }

    /**
     * PATCH /reports/{id}/transmit — Transmission à l'entité (RG-RAP-003)
     */
    public function transmit(Request $request, $id)
    {
        $report = \App\Modules\Report\Models\Report::with('mission.entity')->findOrFail($id);

        if ($report->statut !== 'validé') {
            return response()->json([
                'success' => false,
                'message' => 'Le rapport doit être validé avant transmission (RG-RAP-002)',
                'errors'  => null
            ], 422);
        }

        $report->update([
            'statut'            => 'transmis',
            'date_transmission' => now(),
        ]);

        // TODO: envoyer email au responsable de l'entité (RG-RAP-003)
        // Mail::to($report->mission->entity->responsable->email)->send(new ReportTransmitted($report));

        return response()->json([
            'success' => true,
            'data'    => $report->fresh(),
            'message' => 'Rapport transmis à l\'entité supervisée',
            'errors'  => null
        ]);
    }

    /**
     * PATCH /reports/{id}/acknowledge — Accusé de réception (RG-RAP-003)
     * Le responsable d'entité accuse réception
     */
    public function acknowledge($id)
    {
        $report = \App\Modules\Report\Models\Report::findOrFail($id);

        if ($report->statut !== 'transmis') {
            return response()->json([
                'success' => false,
                'message' => 'Le rapport n\'a pas encore été transmis',
                'errors'  => null
            ], 422);
        }

        $report->update([
            'accuse_reception_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $report->fresh(),
            'message' => 'Accusé de réception enregistré',
            'errors'  => null
        ]);
    }

    /**
     * POST /missions/{id}/pdf — Générer le PDF
     */
    public function generatePdf($missionId)
    {
        \App\Modules\Report\Jobs\GenerateReportJob::dispatch($missionId);

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'Génération PDF lancée en arrière-plan',
            'errors'  => null
        ]);
    }
}
