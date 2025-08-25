<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Auth; 
use App\Http\Controllers\LoginController;
use App\Http\Controllers\CarbonRecordController;
use App\Http\Controllers\HarvestManagementController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/login', 'LoginController@index')->name('student.login');
Route::post('/login/check', 'LoginController@check')->name('login.check');

Route::get('/', 'LoginController@logout'); 

// Route::middleware(['auth', 'adminAccess'])->group(function () {
//     Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('pages.dashboard');
//     Route::get('/farm-data', [DashboardController::class, 'farmData'])->name('pages.farm-data');
//     Route::get('/analytics', [DashboardController::class, 'analytics'])->name('pages.analytics');
//     Route::get('/carbon-sequestration', [DashboardController::class, 'carbonSequestration'])->name('pages.carbon-sequestration');
//     Route::get('/harvest-management', [DashboardController::class, 'harvestManagement'])->name('pages.harvest-management');
//     Route::get('/backup', [DashboardController::class, 'backup'])->name('pages.backup');
//     Route::get('/feedback', [DashboardController::class, 'feedback'])->name('pages.feedback');
//     Route::get('/admin', 'AdminController@index')->middleware('auth', 'role:admin,superadmin');
//     Route::get('/superadmin', 'AdminController@index')->middleware('auth', 'role:superadmin');
// });
//for carbon record saving
// Route::post('/carbon-records', 'CarbonRecordController@store');
// Route::get('/carbon-sequestration/create', 'CarbonRecordController@create')->name('pages.carbon-records-create');

Route::middleware('auth')->group(function () {
    Route::get('/profile', 'ProfileController@index')->name('profile.index');
    Route::post('/profile/update', 'ProfileController@update')->name('profile.update');
    Route::put('/profile', 'ProfileController@update')->name('profile.update');

});


// Superadmin routes
Route::group(['middleware' => ['auth', 'role:superadmin']], function () {
    Route::get('/superadmin', 'DashboardController@index')->name('superadmin.dashboard');
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

    Route::get('/feedback', 'FeedbackController@index')->name('pages.feedback');
    Route::get('/activity-log', 'ActivityLogController@index')->name('pages.activity-log');
});

// User routes
Route::group(['middleware' => ['auth', 'role:user|admin|superadmin']], function () {
    Route::get('/user', 'DashboardController@index')->name('user.dashboard');
    Route::get('/analytics', 'TreeController@index')->name('pages.analytics');
    Route::get('/feedback', 'BackupController@index')->name('pages.feedback');
    Route::get('/activity-log', 'ActivityLogController@index')->name('pages.activity-log');
});

//tree
Route::get('/trees/import', function () {
    return view('trees.import');
})->name('trees.import');
Route::post('/trees/import', 'TreeController@importExcel');

Route::get('/trees/codes', 'TreeController@getCodes');
Route::get('/trees/check-code', 'TreeController@checkCode');
Route::get('/trees/map', 'TreeController@showMap')->name('trees.map');
Route::get('/trees/data', 'TreeController@getTreeData')->name('trees.data');
//store tree manually
Route::post('/trees/store', 'TreeController@store')->name('trees.store');

//logout
Route::post('/logout', 'LoginController@logout')->name('logout');
//Route::post('/logout', ['UserDashboardController@logout'])->middleware('auth', 'role:admin,superadmin')->name('logout');


// Route::middleware(['auth'])->group(function () {
//     Route::get('/harvest-management', [HarvestManagementController::class, 'index'])->name('harvest.index');
//     Route::post('/harvest-management/store', [HarvestManagementController::class, 'store'])->name('harvest.store');
//     Route::post('/harvest-management/import', [HarvestManagementController::class, 'import'])->name('harvest.import');
//     Route::post('/harvest-management/{treeCode}/predict', [HarvestManagementController::class, 'predict'])->name('harvest.predict');
// });
