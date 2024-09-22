<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WorkflowController;
use App\Http\Controllers\AudioController;
use App\Models\AudioRequest;

Route::get('/', function () {
    return "r2d2";
});


Route::get('/audio/download/{audioRequest}', function (AudioRequest $audioRequest) {
    return response()->download(storage_path('app/' . $audioRequest->file_path));
})->name('audio.download');
Route::post('/generate-audio', [AudioController::class, 'generate']);
Route::get('/workflow/{any}', [WorkflowController::class, 'handle'])
    ->where('any', '.*'); // Matches any route after /workflow/