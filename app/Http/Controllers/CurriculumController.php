<?php

namespace App\Http\Controllers;

use App\Models\Curriculum;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CurriculumController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'lesson' => 'required|string|max:255',
            'unit' => 'required|string|max:255',
            'file_path' => 'nullable|string|max:255',
            'prompt' => 'nullable|string',
            'pdf_content' => 'nullable|string'
        ]);

        $curriculum = Curriculum::create($validated);

        return response()->json([
            'message' => 'Curriculum created successfully',
            'data' => $curriculum
        ], 201);
    }
} 