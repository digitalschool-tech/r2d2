<?php

use App\Http\Controllers\AudioController;
use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\CurriculumExportController;
use App\Http\Controllers\QuizController;

Route::post('/generate-audio', [AudioController::class, 'generate']);
Route::post('/generate-quiz', [QuizController::class, 'generateQuizFromCurriculum']);
Route::post('/curriculum', [CurriculumController::class, 'store']);
Route::get('/export-lessons', [CurriculumExportController::class, 'exportCsv']);
Route::get('/curriculums/student-pov', [CurriculumController::class, 'studentPOV']);
Route::post('/quizzes/{quiz}/submit', [QuizController::class, 'submitQuiz']);