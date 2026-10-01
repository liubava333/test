<?php

use App\Http\Controllers\ImageController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportParseController;
use App\Http\Controllers\TestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/reports/generate', [ReportController::class, 'generate']);
Route::get('/reports/{id}/download', [ReportController::class, 'download']);

Route::post('/parseData', [ReportParseController::class, 'parseData']);
Route::get('/reports-parse/{id}', [ReportParseController::class, 'checkParseStatus']);

Route::get('/images', [ImageController::class, 'index']);
Route::post('/upload-images', [ImageController::class, 'upload']);
Route::put('/images/{id}', [ImageController::class, 'update']);
Route::delete('/images/{id}', [ImageController::class, 'destroy']);
