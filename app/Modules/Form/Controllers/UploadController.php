<?php

namespace App\Modules\Form\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UploadController extends Controller
{
public function store(Request $request)
{
    $request->validate([
        'file' => 'required|image|max:2048' // 2MB max
    ]);

    $path = $request->file('file')->store('uploads', 'public');

    return response()->json([
        'success' => true,
        'data' => [
            'path' => $path,
            'url' => asset('storage/' . $path)
        ],
        'message' => 'Fichier uploadé',
        'errors' => null
    ]);
}
}