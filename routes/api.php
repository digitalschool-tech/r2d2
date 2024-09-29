
<?php

use App\Http\Controllers\AudioController;

Route::post('/generate-audio', [AudioController::class, 'generate']);
