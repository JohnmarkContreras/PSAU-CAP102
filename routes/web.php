<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Auth; 
use App\Http\Controllers\LoginController;
use App\Http\Controllers\CarbonRecordController;
use App\Http\Controllers\HarvestManagementController;
use App\Http\Controllers\PendingGeotagController;
use App\Http\Controllers\TreeController;
use App\Http\Controllers\DeadTreeRequestController;
use App\Http\Controllers\TreeDataController;
use App\Http\Controllers\UserArchiveController;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\HarvestReminderController;
use App\Notifications\SmsNotification;
use App\User;
use App\Tree;

Route::get('/send-sms', 'HarvestReminderController@sendSMSToAllUsers');

Route::middleware('prevent-back-history')->group(function () {
    Route::get('/login', 'LoginController@index')->name('login');
    Route::post('/login/check', 'LoginController@check')->name('login.check');
});

Route::get('/', 'LoginController@logout'); 

//feedback
Route::group(['middleware' => ['auth', 'role:user']], function () {
    Route::get('/feedbacks/create', 'FeedbackController@create')->name('feedback.create');
    Route::post('/feedbacks', 'FeedbackController@store')->name('feedback.store');
});

    //notifications
    // Route::post('/notifications/{id}/read', 'NotificationController@markAsRead')->name('notifications.markAsRead');
    // Route::get('/geotags/{id}', [PendingGeotagController::class, 'show'])->name('geotags.pending');

    Route::middleware(['auth'])->group(function () {
    Route::get('/pending-geotags', 'PendingGeotagTreeController@index')->name('pending-geotags.index');
    Route::post('/pending-geotags/{id}/approve', 'PendingGeotagTreeController@approve')->name('pending-geotags.approve');
    Route::post('/pending-geotags/{id}/reject', 'PendingGeotagTreeController@reject')->name('pending-geotags.reject');
    // Tree Images (new map + data)
    Route::get('/tree-images', 'TreeImageController@index')->name('tree-images.index');
    Route::get('/tree-images/data', 'TreeImageController@data')->name('tree-images.data');
    Route::get('/tree-images/codes', 'TreeImageController@getCodes')->name('tree-images.codes');
    Route::get('/tree-images/create', 'TreeImageController@create')->name('tree-images.create');
    // Route::post('/tree-images/store', 'TreeImageController@store')->name('tree-images.store');
    Route::post('/trees', 'PendingGeotagTreeController@store')->name('trees.store');
    // Route::get('/trees/{tree:code}/edit', 'TreeController@edit')->name('trees.edit');
    // Route::post('/trees/report-dead/{code}', 'TreeController@reportDead')->name('trees.reportDead');
    Route::get('/dead-tree-requests/create', [DeadTreeRequestController::class, 'create'])->name('dead-tree-requests.create');
    Route::get('/dead-tree-requests', [DeadTreeRequestController::class, 'index'])->name('dead-tree-requests.index');
    Route::get('/dead-tree-requests/{id}', [DeadTreeRequestController::class, 'show'])->name('dead-tree-requests.show');
    Route::post('/dead-tree-requests/{id}/approve', [DeadTreeRequestController::class, 'approve'])->name('dead-tree-requests.approve');
    Route::post('/dead-tree-requests/{id}/reject', [DeadTreeRequestController::class, 'reject'])->name('dead-tree-requests.reject');
    Route::post('/dead-tree-requests', [DeadTreeRequestController::class, 'store'])->name('dead-tree-requests.store');
    // Tree Measurements
    Route::resource('tree_measurements', 'TreeMeasurementController')->only(['index','create','store','show']);
    // route that accepts tree code instead of choosing a record
    Route::post('tree-measurements/store-by-code', 'TreeMeasurementController@storeByCode')->name('tree_measurements.store_by_code');
    // Route::get('/tree_measurements/create', 'TreeMeasurementController@create')->name('tree_measurements.create');
    // Route::post('/tree_measurements', 'TreeMeasurementController@store')->name('tree_measurements.store');
    // Carbon Records
    Route::resource('tree_data', 'TreeDataController')->only(['index','create','store','show']);
    Route::get('tree_data/carbon', 'TreeDataController@carbon')->name('tree_data.carbon');
    // compute and save for a single tree_data row
    Route::post('tree_data/{treeData}/compute-carbon', 'TreeDataController@computeCarbon')->name('tree_data.compute-carbon');
    // bulk compute (compute+save for all rows or filtered set)
    Route::post('tree_data/compute-carbon/bulk', 'TreeDataController@computeCarbonBulk')->name('tree_data.compute-carbon.bulk');
    // list only sequestered records
    Route::get('tree_data/sequestered', [TreeDataController::class, 'indexSequestered'])->name('tree_data.sequestered');
    Route::get('analytics/carbon', [TreeDataController::class, 'analyticsCarbon'])->name('analytics.carbon');
    Route::get('analytics/projection', 'TreeDataController@getProjectionAnalytics')->name('analytics.projection');
    Route::get('/harvest-predictions', [App\Http\Controllers\HarvestManagementController::class, 'index']);
    Route::post('/send-reminders', [HarvestReminderController::class, 'sendReminders']) 
    ->name('send.reminders');
    Route::get('/harvests/evaluate', [HarvestManagementController::class, 'evaluate'])
    ->name('harvests.evaluate');
    //profile edit
    Route::get('/profile', 'ProfileController@index')->name('profile.index');
    // Route::post('/profile/update', 'ProfileController@update')->name('profile.update');
    Route::put('/profile', 'ProfileController@update')->name('profile.update');
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
    Route::get('/backup', 'BackupController@index')->name('pages.backup');
    Route::get('/feedbacks', 'FeedbackController@index')->name('feedback.index');
    Route::get('/user_table', 'AdminController@usertable')->name('admin.user_table');
    Route::post('/feedbacks/{feedback}/status', 'FeedbackController@updateStatus')->name('feedback.updateStatus');
    Route::get('/activity-log', 'ActivityLogController@index')->name('pages.activity-log');
    // Route::get('/geotags', 'PendingGeotagController@pending')->name('geotags.pending');
    // Route::patch('/pending-geotags/{id}/reject', 'PendingGeotagController@reject')->name('pending-geotags.reject');
    // Route::post('/pending-geotags/{id}/approve', 'PendingGeotagController@approve')->name('pending-geotags.approve');
    // Route::get('/geotags/pending', 'PendingGeotagController@pending')->name('geotags.pending');
    // Route::get('/geotags/history', 'PendingGeotagController@history')->name('geotags.history');
    Route::post('/notifications/{id}/read', 'NotificationController@markAsRead')->name('notifications.markAsRead');
    Route::get('/notifications', 'NotificationController@index')->name('pages.notifications');
    // Mark all as read
    Route::post('/notifications/mark-all-read', 'NotificationController@markAllRead')->name('notifications.markAllRead');
    // Delete a notification
    Route::delete('/notifications/{id}', 'NotificationController@destroy')->name('notifications.destroy');
    // User Archiving routes
    Route::post('/users/{user}/archive', 'UserArchiveController@archive')->name('users.archive');
    Route::post('/user_archive/{user}/restore', 'UserArchiveController@restore')->name('user_archive.restore');
    Route::get('/user_archive', 'UserArchiveController@index')->name('user_archive.index');
    Route::get('/user_archive/{archive}', 'UserArchiveController@show')->name('user_archive.show');
    // Admin edit user page
    Route::get('/admin/edit_user/{id}', 'AdminController@editUser')->name('admin.edit_user');
    // Handle form submission
    Route::post('/admin/update_user/{id}', 'AdminController@updateUser')->name('admin.update_user');
    //email service
});

// User routes
Route::group(['middleware' => ['auth', 'role:user|admin|superadmin']], function () {
    Route::get('/user', 'DashboardController@index')->name('user.dashboard');
    Route::get('/analytics', 'TreeController@index')->name('pages.analytics');
    Route::get('/feedback', 'BackupController@index')->name('pages.feedback');
    Route::post('/notifications/{id}/read', 'NotificationController@markAsRead')->name('notifications.markAsRead');
    Route::get('/notifications/filter', 'NotificationController@index')->name('pages.notifications.filter');
    // Mark all as read
    Route::post('/notifications/mark-all-read', 'NotificationController@markAllRead')->name('notifications.markAllRead');
    // Delete a notification
    Route::delete('/notifications/{id}', 'NotificationController@destroy')->name('notifications.destroy');
    Route::get('/harvests/upcoming', 'HarvestManagementController@upcoming')->name('harvests.upcoming');
    //mark as done for harvest
    Route::post('/harvests/mark-done', 'HarvestManagementController@markDone')->name('harvests.markDone');
});

//tree
// Route::get('/trees/import', function () {
//     return view('trees.import');
// })->name('trees.import');
// Route::post('/trees/import', 'TreeController@importExcel');

// Route::get('/trees/codes', 'TreeController@getCodes');
// Route::get('/trees/check-code', 'TreeController@checkCode');
// Route::get('/trees/map', 'MapController@showMap')->name('trees.map');
// Route::get('/trees/data', 'TreeController@getTreeData')->name('trees.data');
// //store tree manually
// // Route::post('/trees/store', 'TreeController@store')->name('trees.store');

//logout
Route::post('/logout', 'LoginController@logout')->name('logout');
