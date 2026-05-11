<?php

namespace App\Modules\Mission\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Mission\Models\Mission;
use App\Modules\Mission\Services\MissionService;
use App\Modules\Mission\Requests\CreateMissionRequest;
use App\Modules\Mission\Requests\UpdateMissionRequest;

class MissionController extends Controller
{
    public function __construct(
        private MissionService $service
    ) {}

    /**
     * GET /missions
     */
    public function index()
    {
        $missions = Mission::with([
            'entite',
            'coordinateur'
        ])->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $missions,
            'message' => 'Liste des missions',
            'errors' => null
        ]);
    }

    /**
     * POST /missions
     */
    public function store(CreateMissionRequest $request)
    {
        $mission = $this->service->create(
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'data' => $mission,
            'message' => 'Mission créée',
            'errors' => null
        ], 201);
    }

    /**
     * PUT /missions/{id}
     */
    public function update(
        UpdateMissionRequest $request,
        Mission $mission
    ) {
        $mission = $this->service->update(
            $mission,
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'data' => $mission,
            'message' => 'Mission mise à jour',
            'errors' => null
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

        $url = asset('storage/' . $mission->pdf_path);

        return response()->json([
            'success' => true,
            'data' => [
                'url' => $url
            ],
            'message' => 'PDF disponible',
            'errors' => null
        ]);
    }
}