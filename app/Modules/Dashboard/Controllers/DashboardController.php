<?php

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Mission\Models\Mission;
use App\Modules\Recommendation\Models\Recommendation;
use App\Modules\Entities\Models\Entity;
use App\Models\User;

class DashboardController extends Controller
{
    /**
     * GET /dashboard
     * Tableau de bord — KPIs globaux
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $role = $user->roles->first()?->name;

        // KPIs missions
        $missions = Mission::selectRaw('statut, count(*) as total')
            ->groupBy('statut')
            ->get()
            ->keyBy('statut');

        // KPIs recommandations
        $recommendations = Recommendation::selectRaw('statut, count(*) as total')
            ->groupBy('statut')
            ->get()
            ->keyBy('statut');

        // Recommandations critiques en retard
        $critiquesEnRetard = Recommendation::where('priorite', 'critique')
            ->whereNotIn('statut', ['clôturée', 'non_mise_en_oeuvre'])
            ->where('delai_realisation', '<', now())
            ->count();

        // Taux de conformité global
        $totalRecs = Recommendation::count();
        $closedRecs = Recommendation::where('statut', 'clôturée')->count();
        $tauxConformite = $totalRecs > 0 ? round(($closedRecs / $totalRecs) * 100) : 0;

        $data = [
            'missions' => [
                'total'     => Mission::count(),
                'en_cours'  => $missions->get('en_cours')?->total ?? 0,
                'planifiées'=> $missions->get('planifiée')?->total ?? 0,
                'clôturées' => $missions->get('clôturée')?->total ?? 0,
            ],
            'recommandations' => [
                'total'              => $totalRecs,
                'en_cours'           => $recommendations->get('en_cours')?->total ?? 0,
                'clôturées'          => $closedRecs,
                'critiques_en_retard'=> $critiquesEnRetard,
            ],
            'taux_conformite' => $tauxConformite,
            'entites'         => Entity::count(),
            'utilisateurs'    => User::where('statut', 'actif')->count(),
        ];

        return response()->json([
            'success' => true,
            'data'    => $data,
            'message' => 'Tableau de bord',
            'errors'  => null
        ]);
    }
}