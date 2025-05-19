<?php

use App\Http\Controllers\AudioController;
use App\Http\Controllers\MoodleController;
use App\Http\Controllers\CurriculumController;

Route::post('/generate-audio', [AudioController::class, 'generate']);

// Restricted route with CORS middleware
Route::middleware(['cors.custom'])->group(function () {
    Route::post('/generate-quiz', [MoodleController::class, 'generateH5PAndUpload']);
});

Route::post('/curriculum', [CurriculumController::class, 'store']);