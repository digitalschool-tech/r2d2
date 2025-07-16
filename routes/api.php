<?php

use App\Http\Controllers\AudioController;
use App\Http\Controllers\MoodleController;
use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\CurriculumExportController;
use App\Http\Controllers\QuizController;

Route::post('/generate-audio', [AudioController::class, 'generate']);

// Restricted route with CORS middleware
Route::middleware(['cors.custom'])->group(function () {
    // Route::post('/generate-quiz', [MoodleController::class, 'generateH5PAndUpload']);
    Route::post('/generate-quiz', [QuizController::class, 'generateQuizFromCurriculum']);
});
Route::post('/generate-quiz', [QuizController::class, 'generateQuizFromCurriculum']);
Route::post('/curriculum', [CurriculumController::class, 'store']);
Route::get('/export-lessons', [CurriculumExportController::class, 'exportCsv']);
Route::get('/curriculums/student-pov', [CurriculumController::class, 'studentPOV']);