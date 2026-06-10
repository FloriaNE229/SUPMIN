<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Notification\Controllers\NotificationController;

Route::prefix('notifications')->group(function () {

    Route::get('/', [NotificationController::class, 'index']);

    Route::patch('/{id}/read', [NotificationController::class, 'markRead']);

    Route::patch('/read-all', [NotificationController::class, 'markAllRead']);

    Route::delete('/{id}', [NotificationController::class, 'destroy']);
});