<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PredictionController;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// ── Disease Prediction Endpoints ────────────────────────────────────────────
Route::prefix('predict')->group(function () {
    Route::get('/example', [PredictionController::class, 'example']);
    Route::post('/', [PredictionController::class, 'predict']);
});
