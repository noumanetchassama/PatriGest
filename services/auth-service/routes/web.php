<?php

use App\Http\Controllers\Web\AffectationController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DocumentController;
use App\Http\Controllers\Web\ModuleController;
use App\Http\Controllers\Web\PatrimoineController;
use App\Http\Controllers\Web\TransfertController;
use App\Http\Controllers\Web\ValidationController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Patrimoine
    Route::get('/patrimoine', [PatrimoineController::class, 'index'])->name('patrimoine.index');
    Route::get('/patrimoine/create', [PatrimoineController::class, 'create'])->name('patrimoine.create');
    Route::post('/patrimoine', [PatrimoineController::class, 'store'])->name('patrimoine.store');
    Route::get('/patrimoine/{asset}/edit', [PatrimoineController::class, 'edit'])->whereNumber('asset')->name('patrimoine.edit');
    Route::put('/patrimoine/{asset}', [PatrimoineController::class, 'update'])->whereNumber('asset')->name('patrimoine.update');
    Route::post('/patrimoine/{asset}/submit', [PatrimoineController::class, 'submit'])->whereNumber('asset')->name('patrimoine.submit');
    Route::get('/patrimoine/{asset}/qr', [PatrimoineController::class, 'qr'])->whereNumber('asset')->name('patrimoine.qr');
    Route::get('/patrimoine/{asset}', [PatrimoineController::class, 'show'])->whereNumber('asset')->name('patrimoine.show');

    // Workflows
    Route::get('/affectations', [AffectationController::class, 'index'])->name('affectations.index');
    Route::get('/affectations/create', [AffectationController::class, 'create'])->name('affectations.create');
    Route::post('/affectations', [AffectationController::class, 'store'])->name('affectations.store');
    Route::get('/transferts', [TransfertController::class, 'index'])->name('transferts.index');
    Route::get('/transferts/create', [TransfertController::class, 'create'])->name('transferts.create');
    Route::post('/transferts', [TransfertController::class, 'store'])->name('transferts.store');
    Route::get('/validations', [ValidationController::class, 'index'])->name('validations.index');
    Route::post('/validations/{kind}/{id}', [ValidationController::class, 'decide'])->name('validations.decide');

    // Modules
    Route::get('/inventaires', [ModuleController::class, 'inventaires'])->name('inventaires.index');
    Route::get('/inventaires/create', [ModuleController::class, 'inventoryCreate'])->name('inventaires.create');
    Route::post('/inventaires', [ModuleController::class, 'inventoryStore'])->name('inventaires.store');
    Route::get('/inventaires/{inventory}', [ModuleController::class, 'inventoryShow'])->whereNumber('inventory')->name('inventaires.show');
    Route::post('/inventaires/{inventory}/items', [ModuleController::class, 'inventoryAddItems'])->whereNumber('inventory')->name('inventaires.items');
    Route::post('/inventaires/{inventory}/close', [ModuleController::class, 'inventoryClose'])->whereNumber('inventory')->name('inventaires.close');
    Route::post('/inventaires/items/{item}/check', [ModuleController::class, 'inventoryCheckItem'])->whereNumber('item')->name('inventaires.check');

    Route::get('/incidents', [ModuleController::class, 'incidents'])->name('incidents.index');
    Route::get('/incidents/create', [ModuleController::class, 'incidentCreate'])->name('incidents.create');
    Route::post('/incidents', [ModuleController::class, 'incidentStore'])->name('incidents.store');
    Route::post('/incidents/{incident}/resolve', [ModuleController::class, 'incidentResolve'])->whereNumber('incident')->name('incidents.resolve');
    Route::post('/incidents/{incident}/close', [ModuleController::class, 'incidentClose'])->whereNumber('incident')->name('incidents.close');

    Route::get('/maintenance', [ModuleController::class, 'maintenances'])->name('maintenance.index');
    Route::get('/maintenance/create', [ModuleController::class, 'maintenanceCreate'])->name('maintenance.create');
    Route::post('/maintenance', [ModuleController::class, 'maintenanceStore'])->name('maintenance.store');
    Route::post('/maintenance/{maintenance}/{action}', [ModuleController::class, 'maintenanceAction'])->whereNumber('maintenance')->whereIn('action', ['start', 'complete', 'cancel'])->name('maintenance.action');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->whereNumber('document')->name('documents.download');
    Route::get('/documents', [ModuleController::class, 'documents'])->name('documents.index');
    Route::get('/rapports', [ModuleController::class, 'rapports'])->name('rapports.index');
    Route::get('/rapports/export/{type}', [ModuleController::class, 'rapportsExport'])->whereIn('type', ['assets', 'overview'])->name('rapports.export');
    Route::get('/notifications', [ModuleController::class, 'notifications'])->name('notifications.index');
    Route::post('/notifications/read-all', [ModuleController::class, 'notificationsReadAll'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [ModuleController::class, 'notificationRead'])->whereNumber('id')->name('notifications.read');
    Route::get('/administration', [ModuleController::class, 'administration'])->name('administration.index');
    Route::get('/parametres', [ModuleController::class, 'profil'])->name('profil.show');
    Route::post('/parametres/password', [ModuleController::class, 'updatePassword'])->name('profil.password');
});
