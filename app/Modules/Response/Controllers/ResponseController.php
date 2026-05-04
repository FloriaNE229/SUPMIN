<?php

namespace App\Modules\Response\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Response\Models\Response;
use Illuminate\Support\Str;

class ResponseController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'mission_id' => 'required|exists:missions,id',
            'question_id' => 'required|exists:questions,id',
            'answer' => 'nullable|string'
        ]);

        $response = Response::updateOrCreate(
            [
                //  anti doublon
                'mission_id' => $validated['mission_id'],
                'question_id' => $validated['question_id'],
                'user_id' => $request->user()->id,
            ],
            [
                'id' => Str::uuid(),
                'answer' => $validated['answer'],
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $response,
            'message' => 'Réponse enregistrée'
        ]);
    }
}