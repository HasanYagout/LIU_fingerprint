<?php

use App\Http\Controllers\LocalApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::get('/local-data', [LocalApiController::class, 'index']);
