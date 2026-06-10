<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Modules\Mission\Controllers\MissionController;
use App\Modules\Report\Controllers\ReportController;

Route::prefix('missions')->group(function () {

    Route::get('/', [MissionController::class, 'index']);

    Route::get('/{mission}', [MissionController::class, 'show']);

    Route::post('/', [MissionController::class, 'store'])
        ->middleware('role:admin|coordinateur');

    Route::put('/{mission}', [MissionController::class, 'update'])
        ->middleware('role:admin|coordinateur');

    // RG-MIS-001 + RG-MIS-003 : Validation de mission
    Route::patch('/{mission}/validate', [MissionController::class, 'validateMission'])
        ->middleware('role:admin|coordinateur');

    // RG-MIS-005 : Clôture de mission
    Route::patch('/{mission}/close', [MissionController::class, 'close'])
        ->middleware('role:admin|coordinateur');

    // RG-REC-004 : Recommandations non clôturées
    Route::get('/{mission}/unresolved-recommendations', [MissionController::class, 'unresolvedRecommendations']);

    // PDF
    Route::get('/{mission}/pdf', [MissionController::class, 'pdf']);

    Route::post('/{mission}/generate-pdf', [ReportController::class, 'generatePdf'])
        ->middleware('role:admin|coordinateur');

    // Rapport généré depuis mission
    Route::get('/{id}/report', [ReportController::class, 'buildFromMission']);
});

// Mes missions (agent)
Route::get('/my-missions', function (Request $request) {

    $missions = $request->user()
        ->assignedMissions()
        ->with('entity')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $missions,
        'message' => 'Mes missions',
        'errors' => null
    ]);
});