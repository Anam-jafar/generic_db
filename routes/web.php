<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CollectionController;







Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {

    // Profile URL

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');


    // Collection Show
    Route::get('/', [CollectionController::class, 'index'])->name('collections.index');
    Route::get('/collections/{collectionName}', [CollectionController::class, 'show'])->name('collections.show');

    //Collection Data CRUD
    Route::delete('/collections/{collectionName}/{id}', [CollectionController::class, 'destroy'])->name('collections.destroy');
    Route::get('/collections/{collectionName}/{id}/edit', [CollectionController::class, 'edit'])->name('collections.edit');
    Route::post('/collections/{collection}/{id}', [CollectionController::class, 'update'])->name('collections.update');
    Route::post('/collections/{collectionName}/{id}/restore', [CollectionController::class, 'restore'])->name('collections.restore');


    //Collection Data Upload
    Route::post('/upload-data/{collectionName}', [CollectionController::class, 'bulkUpload'])->name('collection.upload');
   
    // Collection Download
    Route::get('/collections/{collectionName}/download-template', [CollectionController::class, 'download'])->name('collections.downloadTemplate')->defaults('includeData', false);
    Route::get('/collections/{collectionName}/download-data', [CollectionController::class, 'download'])->name('collections.downloadData')->defaults('includeData', true);

    // Collection Creation 
    Route::get('/collection-create', [CollectionController::class, 'create'])->name('collections.create');
    Route::post('collection-store', [CollectionController::class, 'store'])->name('collections.store');

    // Activity , Suggesions
    Route::get('/activity-logs', [CollectionController::class, 'activityLogs'])->name('activity.logs');
    Route::get('/suggestions', [CollectionController::class, 'suggestCollections'])->name('collections.suggestions');


});
