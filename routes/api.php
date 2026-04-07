<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\HistoryController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\TemplateController;

// Public authentication routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/register', [AuthController::class, 'register']);

// Protected API routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::apiResource('departments', DepartmentController::class);
    Route::get('users/trash', [UserController::class, 'trash']);
    Route::post('users/{user}/restore', [UserController::class, 'restore']);
    Route::apiResource('users', UserController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::get('tickets/metrics', [TicketController::class, 'metrics']);
    Route::get('tickets/trash', [TicketController::class, 'trash']);
    Route::post('tickets/{ticket}/restore', [TicketController::class, 'restore']);
    Route::apiResource('tickets', TicketController::class);

    // custom route for nested comments to match frontend expectations
    Route::get('tickets/{ticket}/comments', [TicketController::class, 'comments']);

    Route::apiResource('comments', CommentController::class);
    Route::apiResource('history', HistoryController::class);
    Route::apiResource('notifications', NotificationController::class);
    Route::apiResource('templates', TemplateController::class);
});
