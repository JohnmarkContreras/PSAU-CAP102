<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TreeController;
use App\Http\Controllers\Api\PendingGeotagTreeController;
use App\Http\Controllers\Api\HealthController;

/**
 * Public health check (NO auth, NO DB requirement).
 * Never redirect, never error.
 */
Route::get('ping', [HealthController::class, 'ping'])->middleware(['throttle:1000']);

/**
 * Fast 204 for all preflights under /api/*
 */
Route::options('/{any}', [HealthController::class, 'preflight'])
    ->where('any', '.*');

/**
 * Auth (login is public)
 */
Route::post('login', [AuthController::class, 'login']);

/**
 * Protected API
 */
Route::middleware('auth:sanctum')->group(function () {
    // Protect logout
    Route::post('logout', [AuthController::class, 'logout']);

    // Reads
    Route::get('trees', [TreeController::class, 'index']);
    Route::get('pending-trees', [PendingGeotagTreeController::class, 'index']);
    Route::get('pending-trees/{id}', [PendingGeotagTreeController::class, 'show']);

    // Writes (generous throttle to avoid 429 during batches)
    Route::post('pending-trees', [PendingGeotagTreeController::class, 'store'])->middleware('throttle:2000,1');
    Route::put('pending-trees/{id}', [PendingGeotagTreeController::class, 'update'])->middleware('throttle:2000,1');
    Route::post('pending-trees/upload-image', [PendingGeotagTreeController::class, 'uploadImage'])->middleware('throttle:2000,1');
    Route::post('pending-trees/sync-batch', [PendingGeotagTreeController::class, 'syncBatch'])->middleware('throttle:2000,1');

    // Other actions
    Route::post('check-code', [PendingGeotagTreeController::class, 'checkCode']);
    Route::post('pending-trees/{id}/approve', [PendingGeotagTreeController::class, 'approve']);
    Route::post('pending-trees/{id}/reject',  [PendingGeotagTreeController::class, 'reject']);
});