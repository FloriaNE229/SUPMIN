<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    require __DIR__ . '/v1/auth.php';

    Route::middleware('auth:sanctum')->group(function () {

        require __DIR__ . '/v1/entity.php';
        require __DIR__ . '/v1/mission.php';
        require __DIR__ . '/v1/notification.php';
        require __DIR__ . '/v1/dashboard.php';
        require __DIR__ . '/v1/othersPaths.php';
    });

});