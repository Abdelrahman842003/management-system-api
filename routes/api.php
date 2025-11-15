<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

// Authentication routes (no auth required)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/auth/user', [AuthController::class, 'user'])->name('auth.user');

    // API v1 routes
    Route::prefix('v1')->group(function () {
        // Task CRUD
        Route::apiResource('tasks', TaskController::class);

        // Task status update (for regular users)
        Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus']);

        // Task dependencies
        Route::post('tasks/{task}/dependencies', [TaskController::class, 'attachDependency']);
        Route::delete('tasks/{task}/dependencies/{dependsOnTask}', [TaskController::class, 'detachDependency']);
    });
});
