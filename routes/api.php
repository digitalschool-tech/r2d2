<?php

use App\Http\Controllers\AudioController;
use App\Http\Controllers\MoodleController;

Route::post('/generate-audio', [AudioController::class, 'generate']);

// Restricted route with CORS middleware
// Route::middleware(['cors.custom'])->group(function () {
// });

Route::post('/generate-quiz', [MoodleController::class, 'generateH5PAndUpload']);
