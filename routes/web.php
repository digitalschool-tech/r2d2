<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WorkflowController;
use App\Http\Controllers\AudioController;
use App\Models\AudioRequest;
use App\Http\Controllers\MoodleController;
use Illuminate\Support\Facades\Storage;

// $courseId = 636;
// $grade = 100;
// $name = 'Sample H5P Interactive Content';

// // The relative file path in storage
// $filePath = '/h5p/generated/h5p_6706fcf03bffd.h5p';

// // Call the refactored method with the file path
// $test = MoodleController::uploadH5PActivity($courseId, $filePath, $grade, $name, "test");

// dd($test);

Route::get('/', function () {
    return "r2d2";
});


Route::get('/audio/download/{audioRequest}', function (AudioRequest $audioRequest) {
    return response()->download(storage_path('app/' . $audioRequest->file_path));
})->name('audio.download');
Route::post('/generate-audio', [AudioController::class, 'generate']);
Route::get('/workflow/{any}', [WorkflowController::class, 'handle'])
    ->where('any', '.*'); // Matches any route after /workflow/