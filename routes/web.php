<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CollectionController;

Route::get('/show-warning', [CollectionController::class, 'showWarning'])->name('warning.show');
Route::post('/hide-warning', [CollectionController::class, 'hideWarning'])->name('warning.hide');


Route::get('/activity-logs', [CollectionController::class, 'activityLogs'])->name('activity.logs');
Route::get('/collections/{collectionName}', [CollectionController::class, 'show'])->name('collections.show');
Route::delete('/collections/{collectionName}/{id}', [CollectionController::class, 'destroy'])->name('collections.destroy');
Route::get('/collections/{collectionName}/{id}/edit', [CollectionController::class, 'edit'])->name('collections.edit');
Route::post('/collections/{collection}/{id}', [CollectionController::class, 'update'])->name('collections.update');
Route::post('/collections/{collectionName}/bulk-upload', [CollectionController::class, 'bulkUpload'])->name('collections.bulkUpload');
Route::post('/upload-data/{collectionName}', [CollectionController::class, 'bulkUpload'])->name('upload.data');
Route::get('/collections/{collectionName}/download-template', [CollectionController::class, 'download'])
    ->name('collections.downloadTemplate')
    ->defaults('includeData', false);

Route::get('/collections/{collectionName}/download-data', [CollectionController::class, 'download'])
    ->name('collections.downloadData')
    ->defaults('includeData', true);


Route::get('/suggestions', [CollectionController::class, 'suggestCollections'])->name('collections.suggestions');




Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/', [CollectionController::class, 'index'])->name('collections.index');
});
