<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Auth\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login']);

Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);
Route::post('/password/reset', [AuthController::class, 'resetPassword']);

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/logout', [AuthController::class, 'logout']);

     // Profil utilisateur
    Route::put('/me/profile', [AuthController::class, 'updateProfile']);
    Route::post('/me/change-password', [AuthController::class, 'changePassword']);
 
    // Définir mot de passe personnel (première connexion)
    Route::post('/set-password', [AuthController::class, 'setPersonalPassword']);

    /*
    |--------------------------------------------------------------------------
    | ADMIN ONLY
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin')->group(function () {
        Route::get('/users-list', function () {
            return response()->json([
                'success' => true,
                'message' => 'Liste utilisateurs'
            ]);
        });
    });
});