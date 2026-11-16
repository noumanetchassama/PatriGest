<?php

use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\AssetDocumentController;
use App\Http\Controllers\Api\AssetSubmissionController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TransferController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::get('/health', fn () => response()->json(['status' => 'ok']));

    Route::middleware('auth.patri')->group(function () {

        Route::get('/assets', [AssetController::class, 'index'])
            ->middleware('auth.patri:view-assets');
        Route::get('/assets/stats', [AssetController::class, 'stats'])
            ->middleware('auth.patri:view-assets');
        Route::post('/assets', [AssetController::class, 'store'])
            ->middleware('auth.patri:create-assets');
        Route::get('/asset-categories', [AssetController::class, 'categories'])
            ->middleware('auth.patri:view-assets');
        Route::get('/assets/{asset}', [AssetController::class, 'show'])
            ->middleware('auth.patri:view-assets');
        Route::put('/assets/{asset}', [AssetController::class, 'update'])
            ->middleware('auth.patri:edit-assets');
        Route::delete('/assets/{asset}', [AssetController::class, 'destroy'])
            ->middleware('auth.patri:edit-assets');
        Route::get('/assets/{asset}/locations', [AssetController::class, 'locations'])
            ->middleware('auth.patri:view-assets');
        Route::post('/assets/{asset}/locations', [AssetController::class, 'storeLocation'])
            ->middleware('auth.patri:edit-assets');

        // Affectations (workflow de validation)
        Route::get('/assignments', [AssignmentController::class, 'index'])
            ->middleware('auth.patri:view-assignments');
        Route::post('/assignments', [AssignmentController::class, 'store'])
            ->middleware('auth.patri:create-assignments');
        Route::get('/assignments/{assignment}', [AssignmentController::class, 'show'])
            ->middleware('auth.patri:view-assignments');
        Route::post('/assignments/{assignment}/approve', [AssignmentController::class, 'approve'])
            ->middleware('auth.patri:approve-assignment');
        Route::post('/assignments/{assignment}/reject', [AssignmentController::class, 'reject'])
            ->middleware('auth.patri:approve-assignment');
        Route::post('/assignments/{assignment}/cancel', [AssignmentController::class, 'cancel'])
            ->middleware('auth.patri:view-assignments');

        // Transferts (mutation de structure/dépositaire, même workflow)
        Route::get('/transfers', [TransferController::class, 'index'])
            ->middleware('auth.patri:view-assignments');
        Route::post('/transfers', [TransferController::class, 'store'])
            ->middleware('auth.patri:create-transfer');
        Route::get('/transfers/{transfer}', [TransferController::class, 'show'])
            ->middleware('auth.patri:view-assignments');
        Route::post('/transfers/{transfer}/approve', [TransferController::class, 'approve'])
            ->middleware('auth.patri:approve-transfer');
        Route::post('/transfers/{transfer}/reject', [TransferController::class, 'reject'])
            ->middleware('auth.patri:approve-transfer');
        Route::post('/transfers/{transfer}/cancel', [TransferController::class, 'cancel'])
            ->middleware('auth.patri:view-assignments');

        // Soumissions de biens (brouillon -> actif)
        Route::post('/assets/{asset}/submit', [AssetSubmissionController::class, 'store'])
            ->middleware('auth.patri:submit-assets');
        Route::get('/asset-submissions', [AssetSubmissionController::class, 'index'])
            ->middleware('auth.patri:view-assets');
        Route::get('/asset-submissions/{submission}', [AssetSubmissionController::class, 'show'])
            ->middleware('auth.patri:view-assets');
        Route::post('/asset-submissions/{submission}/approve', [AssetSubmissionController::class, 'approve'])
            ->middleware('auth.patri:approve-asset-creation');
        Route::post('/asset-submissions/{submission}/reject', [AssetSubmissionController::class, 'reject'])
            ->middleware('auth.patri:approve-asset-creation');

        // Maintenances
        Route::get('/maintenances', [MaintenanceController::class, 'index'])
            ->middleware('auth.patri:view-assets');
        Route::post('/assets/{asset}/maintenances', [MaintenanceController::class, 'store'])
            ->middleware('auth.patri:manage-maintenance');
        Route::get('/assets/{asset}/maintenances', [MaintenanceController::class, 'indexForAsset'])
            ->middleware('auth.patri:view-assets');
        Route::get('/maintenances/{maintenance}', [MaintenanceController::class, 'show'])
            ->middleware('auth.patri:view-assets');
        Route::put('/maintenances/{maintenance}', [MaintenanceController::class, 'update'])
            ->middleware('auth.patri:manage-maintenance');
        Route::post('/maintenances/{maintenance}/start', [MaintenanceController::class, 'start'])
            ->middleware('auth.patri:manage-maintenance');
        Route::post('/maintenances/{maintenance}/complete', [MaintenanceController::class, 'complete'])
            ->middleware('auth.patri:manage-maintenance');
        Route::post('/maintenances/{maintenance}/cancel', [MaintenanceController::class, 'cancel'])
            ->middleware('auth.patri:manage-maintenance');

        // Incidents
        Route::get('/incidents', [IncidentController::class, 'index'])
            ->middleware('auth.patri:view-assets');
        Route::post('/assets/{asset}/incidents', [IncidentController::class, 'store'])
            ->middleware('auth.patri:manage-incidents');
        Route::get('/incidents/{incident}', [IncidentController::class, 'show'])
            ->middleware('auth.patri:view-assets');
        Route::post('/incidents/{incident}/resolve', [IncidentController::class, 'resolve'])
            ->middleware('auth.patri:manage-incidents');
        Route::post('/incidents/{incident}/close', [IncidentController::class, 'close'])
            ->middleware('auth.patri:manage-incidents');

        // Documents des biens
        Route::get('/documents', [AssetDocumentController::class, 'index'])
            ->middleware('auth.patri:view-documents');
        Route::post('/assets/{asset}/documents', [AssetDocumentController::class, 'store'])
            ->middleware('auth.patri:manage-documents');
        Route::get('/assets/{asset}/documents', [AssetDocumentController::class, 'indexForAsset'])
            ->middleware('auth.patri:view-documents');
        Route::get('/documents/{document}', [AssetDocumentController::class, 'show'])
            ->middleware('auth.patri:view-documents');
        Route::get('/documents/{document}/download', [AssetDocumentController::class, 'download'])
            ->middleware('auth.patri:view-documents');
        Route::delete('/documents/{document}', [AssetDocumentController::class, 'destroy'])
            ->middleware('auth.patri:manage-documents');

        // Inventaires physiques
        Route::get('/inventories', [InventoryController::class, 'index'])
            ->middleware('auth.patri:view-assets');
        Route::post('/inventories', [InventoryController::class, 'store'])
            ->middleware('auth.patri:manage-inventory');
        Route::get('/inventories/{inventory}', [InventoryController::class, 'show'])
            ->middleware('auth.patri:view-assets');
        Route::get('/inventories/{inventory}/items', [InventoryController::class, 'items'])
            ->middleware('auth.patri:view-assets');
        Route::post('/inventories/{inventory}/items', [InventoryController::class, 'addItems'])
            ->middleware('auth.patri:manage-inventory');
        Route::post('/inventory-items/{item}/check', [InventoryController::class, 'checkItem'])
            ->middleware('auth.patri:manage-inventory');
        Route::post('/inventories/{inventory}/close', [InventoryController::class, 'close'])
            ->middleware('auth.patri:manage-inventory');

        // Rapports
        Route::get('/reports/overview', [ReportController::class, 'overview'])
            ->middleware('auth.patri:view-reports');

        // Notifications personnelles (tout utilisateur authentifié)
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    });
});
