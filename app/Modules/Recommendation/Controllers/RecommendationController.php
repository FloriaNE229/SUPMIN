<?php

namespace App\Modules\Recommendation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Recommendation\Models\Recommendation;

class RecommendationController extends Controller
{

public function store(Request $request)
{
    $data = $request->validate([
        'mission_id' => 'required|exists:missions,id',
        'question_id' => 'nullable|exists:questions,id',
        'reference' => 'required|unique:recommendations,reference',
        'intitule' => 'required|string|max:255',
        'description' => 'required|string',
        'priorite' => 'required|in:critique,majeur,mineur',
        'responsable_id' => 'required|exists:users,id',
        'delai_realisation' => 'required|date',
    ]);

    $rec = Recommendation::create([
        ...$data,
        'id' => \Illuminate\Support\Str::uuid(),
        'creee_par' => $request->user()->id,
    ]);

    return response()->json([
        'success' => true,
        'data' => $rec
    ], 201);
}
}