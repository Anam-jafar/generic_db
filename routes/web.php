<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CollectionController;







Route::get('/generic/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/generic/register', [AuthController::class, 'register']);
Route::get('/generic/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/generic/login', [AuthController::class, 'login']);
Route::post('/generic/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {

    // Profile URL

    Route::get('/generic/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::get('/generic/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/generic/profile', [ProfileController::class, 'update'])->name('profile.update');


    // Collection Show
    Route::get('/generic', [CollectionController::class, 'index'])->name('collections.index');
    Route::get('/generic/collections/{collectionName}', [CollectionController::class, 'show'])->name('collections.show');

    //Collection Data CRUD
    Route::delete('/generic/collections/{collectionName}/{id}', [CollectionController::class, 'destroy'])->name('collections.destroy');
    Route::get('/generic/collections/{collectionName}/{id}/edit', [CollectionController::class, 'edit'])->name('collections.edit');
    Route::post('/generic/collections/{collection}/{id}', [CollectionController::class, 'update'])->name('collections.update');
    Route::post('/generic/collections/{collectionName}/{id}/restore', [CollectionController::class, 'restore'])->name('collections.restore');


    //Collection Data Upload
    Route::post('/generic/upload-data/{collectionName}', [CollectionController::class, 'bulkUpload'])->name('collection.upload');
   
    // Collection Download
    Route::get('/generic/collections/{collectionName}/download-template', [CollectionController::class, 'download'])->name('collections.downloadTemplate')->defaults('includeData', false);
    Route::get('/generic/collections/{collectionName}/download-data', [CollectionController::class, 'download'])->name('collections.downloadData')->defaults('includeData', true);

    // Collection Creation 
    Route::get('/generic/collection-create', [CollectionController::class, 'create'])->name('collections.create');
    Route::post('/generic/collection-store', [CollectionController::class, 'store'])->name('collections.store');

    // Activity , Suggesions
    Route::get('/generic/activity-logs', [CollectionController::class, 'activityLogs'])->name('activity.logs');
    Route::get('/generic/suggestions', [CollectionController::class, 'suggestCollections'])->name('collections.suggestions');

    Route::get('/generic/admin/users', [AuthController::class, 'showUsers'])->name('admin.users');
    Route::get('/generic/admin/users/{id}/edit', [AuthController::class, 'editUser'])->name('users.edit');
    Route::put('/generic/admin/users/{id}', [AuthController::class, 'updateUser'])->name('users.update');
    Route::delete('/generic/admin/users/{id}', [AuthController::class, 'destroyUser'])->name('users.destroy');
    Route::get('/generic/admin/users/{id}/logs', [AuthController::class, 'viewActivityLogs'])->name('users.activityLogs');



});

// Route::middleware(['auth', 'is_admin'])->group(function () {
//     Route::get('admin/users', [AuthController::class, 'showUsers'])->name('admin.users');
//     Route::get('admin/users/{id}/edit', [AuthController::class, 'editUser'])->name('users.edit');
//     Route::put('admin/users/{id}', [AuthController::class, 'updateUser'])->name('users.update');
//     Route::delete('admin/users/{id}', [AuthController::class, 'destroyUser'])->name('users.destroy');
//     Route::get('admin/users/{id}/logs', [AuthController::class, 'viewActivityLogs'])->name('users.activityLogs');
// });

