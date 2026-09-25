<?php

use App\Http\Controllers\ImageController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportParseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/parseData', [ReportParseController::class, 'parseData']);
Route::post('/reports/generate', [ReportController::class, 'generate']);
Route::get('/reports/{id}/status', [ReportController::class, 'checkStatus']);
Route::get('/reports/{id}/download', [ReportController::class, 'download']);
Route::get('/reports-parse/{id}', [ReportParseController::class, 'checkParseStatus']);
Route::post('/upload-images', [ImageController::class, 'upload']);
Route::delete('/images/{id}', [ImageController::class, 'destroy']);
Route::post('/images/{id}', [ImageController::class, 'update']); // Используем POST, так как PUT с файлами в PHP имеет баги
Route::get('/images', [ImageController::class, 'index']); // Получить все картинки
