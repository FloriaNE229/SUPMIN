<?php
// routes/v1/othersPaths.php
// Ce fichier est chargé DANS le middleware auth:sanctum donc pas besoin de le répéter

use Illuminate\Support\Facades\Route;
use App\Modules\User\Controllers\UserController;
use App\Modules\Mission\Controllers\MissionController;
use App\Modules\Entities\Controllers\EntityController;
use App\Modules\Form\Controllers\FormController;
use App\Modules\Form\Controllers\UploadController;
use App\Modules\Recommendation\Controllers\RecommendationController;
use App\Modules\Report\Controllers\ReportController;
use App\Modules\Notification\Controllers\NotificationController;
use App\Modules\Response\Controllers\ResponseController;
use App\Http\Controllers\ResponseSyncController;
use App\Modules\Dashboard\Controllers\DashboardController;
use App\Modules\Auth\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| DASHBOARD
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', [DashboardController::class, 'index']);

/*
|--------------------------------------------------------------------------
| USERS — Admin uniquement
|--------------------------------------------------------------------------
*/
Route::prefix('users')->middleware('role:admin')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('/{user}', [UserController::class, 'show']);
    Route::put('/{user}', [UserController::class, 'update']);
    Route::delete('/{user}', [UserController::class, 'destroy']);
    Route::patch('/{user}/suspend', [UserController::class, 'suspend']);
    Route::patch('/{user}/activate', [UserController::class, 'activate']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/check-inactive', [UserController::class, 'checkInactive']);
});

/*
|--------------------------------------------------------------------------
| ENTITIES
|--------------------------------------------------------------------------
*/
Route::prefix('entities')->group(function () {
    Route::get('/', [EntityController::class, 'index']);
    Route::get('/{entity}', [EntityController::class, 'show']);
    Route::post('/', [EntityController::class, 'store'])->middleware('role:admin');
    Route::put('/{entity}', [EntityController::class, 'update'])->middleware('role:admin');
    Route::delete('/{entity}', [EntityController::class, 'destroy'])->middleware('role:admin');
});

/*
|--------------------------------------------------------------------------
| MISSIONS
|--------------------------------------------------------------------------
*/
Route::prefix('missions')->group(function () {
    Route::get('/', [MissionController::class, 'index']);
    Route::get('/{mission}', [MissionController::class, 'show']);
    Route::post('/', [MissionController::class, 'store'])
        ->middleware('role:admin|coordinateur');
    Route::put('/{mission}', [MissionController::class, 'update'])
        ->middleware('role:admin|coordinateur');
    Route::patch('/{mission}/validate', [MissionController::class, 'validateMission'])
        ->middleware('role:admin|coordinateur');
    Route::patch('/{mission}/close', [MissionController::class, 'close'])
        ->middleware('role:admin|coordinateur');
    Route::get('/{mission}/unresolved-recommendations', [MissionController::class, 'unresolvedRecommendations']);
    Route::get('/{mission}/pdf', [MissionController::class, 'pdf']);
    Route::post('/{mission}/generate-pdf', [ReportController::class, 'generatePdf'])
        ->middleware('role:admin|coordinateur');
    Route::get('/{id}/report', [ReportController::class, 'buildFromMission']);
});

Route::get('/my-missions', function (\Illuminate\Http\Request $request) {
    $missions = $request->user()->assignedMissions()->with('entity')->get();
    return response()->json(['success' => true, 'data' => $missions, 'errors' => null]);
});

/*
|--------------------------------------------------------------------------
| FORMS
|--------------------------------------------------------------------------
*/
Route::prefix('forms')->group(function () {
    Route::get('/', [FormController::class, 'index']);
    Route::post('/', [FormController::class, 'store'])
        ->middleware('role:admin|coordinateur|agent');
    Route::get('/{form}', [FormController::class, 'show']);
    Route::put('/{form}', [FormController::class, 'update'])
        ->middleware('role:admin|coordinateur|agent');
    Route::post('/{id}/duplicate', [FormController::class, 'duplicate'])
        ->middleware('role:admin|coordinateur');
    Route::patch('/{form}/publish', [FormController::class, 'publish'])
        ->middleware('role:admin|coordinateur');
});

/*
|--------------------------------------------------------------------------
| RECOMMENDATIONS
|--------------------------------------------------------------------------
*/
Route::prefix('recommendations')->group(function () {
    Route::get('/', [RecommendationController::class, 'index']);
    Route::post('/', [RecommendationController::class, 'store'])
        ->middleware('role:admin|coordinateur|agent');
    Route::get('/{recommendation}', [RecommendationController::class, 'show']);
    Route::patch('/{recommendation}/status', [RecommendationController::class, 'updateStatus']);
    Route::get('/{recommendation}/tracking', [RecommendationController::class, 'tracking']);
    Route::post('/{recommendation}/validate', [RecommendationController::class, 'validateRec'])
        ->middleware('role:admin|validateur|coordinateur');
    Route::post('/{recommendation}/revision', [RecommendationController::class, 'requestRevision'])
        ->middleware('role:admin|validateur|coordinateur');
});

/*
|--------------------------------------------------------------------------
| REPORTS
|--------------------------------------------------------------------------
*/
Route::prefix('reports')->group(function () {
    Route::get('/', [ReportController::class, 'index']);
    Route::get('/{id}', [ReportController::class, 'show']);
    Route::patch('/{id}/validate', [ReportController::class, 'validate'])
        ->middleware('role:admin|coordinateur');
    Route::patch('/{id}/transmit', [ReportController::class, 'transmit'])
        ->middleware('role:admin|coordinateur');
    Route::patch('/{id}/acknowledge', [ReportController::class, 'acknowledge'])
        ->middleware('role:admin|responsable_entite');
});

/*
|--------------------------------------------------------------------------
| RESPONSES
|--------------------------------------------------------------------------
*/
Route::post('/responses', [ResponseController::class, 'store']);
Route::post('/responses/sync', [ResponseSyncController::class, 'sync']);

/*
|--------------------------------------------------------------------------
| UPLOADS
|--------------------------------------------------------------------------
*/
Route::post('/uploads', [UploadController::class, 'store']);

/*
|--------------------------------------------------------------------------
| NOTIFICATIONS
|--------------------------------------------------------------------------
*/
Route::prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::patch('/{id}/read', [NotificationController::class, 'markRead']);
    Route::patch('/read-all', [NotificationController::class, 'markAllRead']);
    Route::delete('/{id}', [NotificationController::class, 'destroy']);
});