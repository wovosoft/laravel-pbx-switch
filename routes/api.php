<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get("tts", [\App\Http\Controllers\TTSController::class, "synthesizeWithGoogleClient"]);
//Route::get("tts", [\App\Http\Controllers\TTSController::class, "generateWithFlite"]);
//Route::get("tts", [\App\Http\Controllers\TTSController::class, "generateWithFlite"]);
