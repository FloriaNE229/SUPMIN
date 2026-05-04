<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Modules\Response\Models\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResponseSyncController extends Controller
{
    public function sync(Request $request)
    {
        $responses = $request->all();

        DB::beginTransaction();

        try {

            foreach ($responses as $item) {

                foreach ($item['data'] as $questionId => $value) {

                    Response::updateOrCreate(
                        [
                            // anti doublon
                            'mission_id' => $item['mission_id'],
                            'question_id' => $questionId,
                            'user_id' => $request->user()->id,
                        ],
                        [
                            'id' => Str::uuid(), // UUID valide
                            'answer' => $value,
                            'created_at' => $item['created_at'],
                            'updated_at' => $item['updated_at'],
                        ]
                    );
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sync réussie'
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur sync',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}