<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::prefix('notifications')->group(function () {

    // Liste des notifications
    Route::get('/', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => $request->user()->notifications,
            'message' => 'Liste des notifications',
            'errors' => null
        ]);
    });

    // Notifications non lues
    Route::get('/unread', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => $request->user()->unreadNotifications,
            'message' => 'Notifications non lues',
            'errors' => null
        ]);
    });

    // Marquer une notification comme lue
    Route::post('/{id}/read', function (Request $request, string $id) {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue',
            'errors' => null
        ]);
    });

    // Tout marquer comme lu
    Route::post('/read-all', function (Request $request) {
        $request->user()
            ->unreadNotifications
            ->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Toutes les notifications ont été marquées comme lues',
            'errors' => null
        ]);
    });
});