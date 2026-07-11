<?php

use App\Http\Controllers\Api\V1\ApprovalRequestController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\PushTokenController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\UploadController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/token', [AuthController::class, 'token']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/auth/revoke', [AuthController::class, 'revoke']);
        Route::get('/me', [AuthController::class, 'me']);

        Route::post('/push-tokens', [PushTokenController::class, 'store']);
        Route::post('/push-tokens/revoke', [PushTokenController::class, 'destroy']);

        Route::get('/tasks', [TaskController::class, 'index']);
        Route::get('/tasks/{task}', [TaskController::class, 'show']);
        Route::post('/tasks', [TaskController::class, 'store']);
        Route::patch('/tasks/{task}', [TaskController::class, 'update']);

        Route::post('/uploads/task-photo', [UploadController::class, 'taskPhoto']);

        Route::get('/reports/dashboard', [ReportController::class, 'dashboard']);

        Route::get('/departments', [DepartmentController::class, 'index']);
        Route::get('/departments/{department}', [DepartmentController::class, 'show']);

        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show']);

        Route::get('/approval-requests', [ApprovalRequestController::class, 'index']);
        Route::get('/approval-requests/{approvalRequest}', [ApprovalRequestController::class, 'show']);
        Route::post('/approval-requests/{approvalRequest}/approve', [ApprovalRequestController::class, 'approve']);
        Route::post('/approval-requests/{approvalRequest}/reject', [ApprovalRequestController::class, 'reject']);
    });
});
