<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WorkflowController;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/workflow/{any}', [WorkflowController::class, 'handle'])
    ->where('any', '.*'); // Matches any route after /workflow/