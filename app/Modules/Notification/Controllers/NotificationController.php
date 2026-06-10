<?php

namespace App\Modules\Notification\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * GET /notifications — Liste les notifications de l'utilisateur connecté
     */
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->get()
            ->map(function ($n) {
                return [
                    'id'      => $n->id,
                    'type'    => $n->data['type'] ?? 'systeme',
                    'titre'   => $n->data['titre'] ?? '',
                    'message' => $n->data['message'] ?? '',
                    'lu'      => !is_null($n->read_at),
                    'temps'   => $n->created_at->diffForHumans(),
                    'created_at' => $n->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $notifications,
            'unread'  => $notifications->where('lu', false)->count(),
            'message' => 'Notifications',
            'errors'  => null
        ]);
    }

    /**
     * PATCH /notifications/{id}/read — Marquer une notification comme lue
     */
    public function markRead($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'Notification lue',
            'errors'  => null
        ]);
    }

    /**
     * PATCH /notifications/read-all — Marquer toutes comme lues
     */
    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'Toutes les notifications marquées comme lues',
            'errors'  => null
        ]);
    }

    /**
     * DELETE /notifications/{id} — Supprimer une notification
     */
    public function destroy($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->delete();

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'Notification supprimée',
            'errors'  => null
        ]);
    }
}
