<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PredictionController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/get-started', function () {
    return view('prediction-form');
});

Route::post('/api/predict', [PredictionController::class, 'predict']);
Route::get('/export-pdf', [PredictionController::class, 'exportPdf']);
