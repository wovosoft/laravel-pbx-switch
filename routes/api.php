<?php

use App\Http\Controllers\TTSController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get("tts", [TTSController::class, "synthesizeWithGoogleClient"]);
Route::get("tts-ivr-menu", [TTSController::class, "getIvrMenu"]);

//Route::get("tts", [\App\Http\Controllers\TTSController::class, "generateWithFlite"]);
//Route::get("tts", [\App\Http\Controllers\TTSController::class, "generateWithFlite"]);
