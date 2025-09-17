<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Auth; 
use App\Http\Controllers\LoginController;
use App\Http\Controllers\CarbonRecordController;
use App\Http\Controllers\HarvestManagementController;
use App\Http\Controllers\PendingGeotagController;

Route::middleware('prevent-back-history')->group(function () {
    Route::get('/login', 'LoginController@index')->name('login');
    Route::post('/login/check', 'LoginController@check')->name('login.check');
});

Route::get('/', 'LoginController@logout'); 

Route::middleware('auth')->group(function () {
    Route::get('/profile', 'ProfileController@index')->name('profile.index');
    Route::post('/profile/update', 'ProfileController@update')->name('profile.update');
    Route::put('/profile', 'ProfileController@update')->name('profile.update');

});

//feedback
Route::group(['middleware' => ['auth', 'role:user']], function () {
    Route::get('/feedbacks/create', 'FeedbackController@create')->name('feedback.create');
    Route::post('/feedbacks', 'FeedbackController@store')->name('feedback.store');
});


Route::middleware(['auth'])->group(function () {
    Route::get('/geotags/pending', [PendingGeotagController::class, 'index'])->name('pending-geotags.index');
    Route::post('/geotags/{id}/approve', [PendingGeotagController::class, 'approve'])->name('pending-geotags.approve');
    Route::post('/geotags/{id}/reject', [PendingGeotagController::class, 'reject'])->name('pending-geotags.reject');
});


// Superadmin routes
Route::group(['middleware' => ['auth', 'role:superadmin']], function () {
    Route::get('/superadmin', 'DashboardController@index')->name('superadmin.dashboard');
    Route::get('/harvest-records/filter', 'DashboardController@filterHarvests')->name('harvest.filter');
    Route::get('/farm-data', 'SuperAdminController@farmData')->name('pages.farm-data');
    Route::get('/analytics', 'TreeController@index')->name('pages.analytics');
    Route::get('/harvest-management', 'HarvestManagementController@index')->name('pages.harvest-management');
    Route::post('/harvest-management/store', 'HarvestManagementController@store')->name('harvest.store');
    Route::post('/harvest-management/import', 'HarvestManagementController@import')->name('harvest.import');
    Route::post('/harvest-management/predict-all', 'HarvestManagementController@predictAll')->name('harvest.predictAll');
    Route::get('/backup', 'BackupController@index')->name('pages.backup');
    Route::get('/feedback', 'FeedbackController@index')->name('pages.feedback');
    Route::get('/accounts', 'SuperAdminController@accounts')->name('pages.accounts');
    Route::delete('/accounts/{id}', 'SuperAdminController@deleteAccount')->name('superadmin.delete.account');
    Route::get('/create-account', 'SuperAdminController@createAccount')->name('create.account');
    Route::post('/create-account', 'SuperAdminController@storeAccount')->name('store.account');
    Route::get('/activity-log', 'ActivityLogController@index')->name('pages.activity-log');

});

// Admin routes
Route::group(['middleware' => ['auth', 'role:admin|superadmin']], function () {
    Route::get('/admin', 'DashboardController@index')->name('admin.dashboard');
    Route::get('/farm-data', 'AdminController@farmData')->name('pages.farm-data');
    Route::get('/analytics', 'TreeController@index')->name('pages.analytics');
    Route::get('/harvest-management', 'HarvestManagementController@index')->name('pages.harvest-management');
    Route::post('/harvest-management/store', 'HarvestManagementController@store')->name('harvest.store');
    Route::post('/harvest-management/import', 'HarvestManagementController@import')->name('harvest.import');
    Route::post('/harvest-management/predict-all', 'HarvestManagementController@predictAll')->name('harvest.predictAll');
    Route::get('/feedbacks', 'FeedbackController@index')->name('feedback.index');
    Route::get('/user-table', 'AdminController@usertable')->name('admin.user-table');
    Route::post('/feedbacks/{feedback}/status', 'FeedbackController@updateStatus')->name('feedback.updateStatus');
    Route::get('/activity-log', 'ActivityLogController@index')->name('pages.activity-log');
    Route::get('/geotags/pending', 'TreeController@pending')->name('geotags.pending');
    Route::post('/geotags/{id}/approve', 'PendingGeotagController@approve')->name('pending-geotags.approve');
    Route::post('/geotags/{id}/reject', 'PendingGeotagController@reject')->name('pending-geotags.reject');
});

// User routes
Route::group(['middleware' => ['auth', 'role:user|admin|superadmin']], function () {
    Route::get('/user', 'DashboardController@index')->name('user.dashboard');
    Route::get('/analytics', 'TreeController@index')->name('pages.analytics');
    Route::get('/feedback', 'BackupController@index')->name('pages.feedback');
});

//tree
Route::get('/trees/import', function () {
    return view('trees.import');
})->name('trees.import');
Route::post('/trees/import', 'TreeController@importExcel');

Route::get('/trees/codes', 'TreeController@getCodes');
Route::get('/trees/check-code', 'TreeController@checkCode');
Route::get('/trees/map', 'MapController@showMap')->name('trees.map');
Route::get('/trees/data', 'TreeController@getTreeData')->name('trees.data');
//store tree manually
Route::post('/trees/store', 'TreeController@store')->name('trees.store');

//logout
Route::post('/logout', 'LoginController@logout')->name('logout');


