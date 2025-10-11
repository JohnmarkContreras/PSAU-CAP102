<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MobileGeotagMetadataController;
use App\Http\Controllers\MobileGeotagController;



// Public routes
Route::post('login', 'Api\AuthController@login');
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', 'Api\AuthController@logout');
        Route::get('trees', 'Api\TreeController@index');
        Route::get('pending-trees', 'Api\PendingTreeController@index');
        Route::get('pending-trees/{id}', 'Api\PendingTreeController@show');
        Route::post('pending-trees', 'Api\PendingTreeController@store');
        Route::put('pending-trees/{id}', 'Api\PendingTreeController@update');
        Route::post('pending-trees/upload-image', 'Api\PendingTreeController@uploadImage');
        Route::post('pending-trees/sync-batch', 'Api\PendingTreeController@syncBatch');
        Route::post('check-code', 'Api\PendingTreeController@checkCode');
        Route::post('pending-trees/{id}/approve', 'Api\PendingTreeController@approve');
        Route::post('pending-trees/{id}/reject', 'Api\PendingTreeController@reject');
});