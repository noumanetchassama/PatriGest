<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\StructureController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Authentification
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');

        // Utilisateurs
        Route::apiResource('users', UserController::class)
            ->middleware('permission:manage-users');

        // Rôles et permissions
        Route::get('/roles', [RoleController::class, 'index'])
            ->middleware('permission:manage-roles');
        Route::post('/roles', [RoleController::class, 'store'])
            ->middleware('permission:manage-roles');
        Route::put('/roles/{role}', [RoleController::class, 'update'])
            ->middleware('permission:manage-roles');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
            ->middleware('permission:manage-roles');

        Route::get('/permissions', [PermissionController::class, 'index'])
            ->middleware('permission:manage-roles');

        // Structures administratives
        Route::apiResource('structures', StructureController::class)
            ->middleware('permission:manage-structures');
        Route::get('/structures-tree', [StructureController::class, 'tree']);

        // Journal d'audit
        Route::get('/audit-logs', [AuditLogController::class, 'index'])
            ->middleware('permission:view-audit-logs');
    });
});
