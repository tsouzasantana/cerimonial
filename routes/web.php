<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ChecklistTemplateController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractItemController;
use App\Http\Controllers\ContractTaskController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentFileController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\InstallmentController;
use App\Http\Controllers\OccurrenceController;
use App\Http\Controllers\OccurrenceTypeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicContractController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\VendorServiceTypeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', DashboardController::class)->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('clients', ClientController::class);
    Route::post('clients/{client}/restore', [ClientController::class, 'restore'])->name('clients.restore');

    Route::resource('services', ServiceController::class)->except(['show']);
    Route::post('services/{service}/restore', [ServiceController::class, 'restore'])->name('services.restore');

    Route::resource('contracts', ContractController::class);
    Route::post('contracts/{contract}/restore', [ContractController::class, 'restore'])->name('contracts.restore');
    Route::post('contracts/{contract}/pdf', [ContractController::class, 'generatePdf'])->name('contracts.pdf');
    Route::get('contracts/{contract}/pdf', [ContractController::class, 'downloadPdf'])->name('contracts.pdf.download');
    Route::post('contracts/{contract}/send-email', [ContractController::class, 'sendContractEmail'])->name('contracts.send-email');

    Route::post('contracts/{contract}/items', [ContractItemController::class, 'store'])->name('contract-items.store');
    Route::delete('contracts/{contract}/items/{item}', [ContractItemController::class, 'destroy'])->name('contract-items.destroy');

    Route::post('contracts/{contract}/installments', [InstallmentController::class, 'store'])->name('installments.store');
    Route::post('contracts/{contract}/installments/batch', [InstallmentController::class, 'storeBatch'])->name('installments.store-batch');
    Route::put('contracts/{contract}/installments/{installment}', [InstallmentController::class, 'update'])->name('installments.update');
    Route::delete('contracts/{contract}/installments/{installment}', [InstallmentController::class, 'destroy'])->name('installments.destroy');
    Route::post('contracts/{contract}/installments/{installment}/restore', [InstallmentController::class, 'restore'])->name('installments.restore');

    Route::post('contracts/{contract}/occurrences', [OccurrenceController::class, 'store'])->name('occurrences.store');
    Route::delete('contracts/{contract}/occurrences/{occurrence}', [OccurrenceController::class, 'destroy'])->name('occurrences.destroy');
    Route::post('contracts/{contract}/occurrences/{occurrence}/restore', [OccurrenceController::class, 'restore'])->name('occurrences.restore');
    Route::get('contracts/{contract}/occurrences/{occurrence}/attachment', [OccurrenceController::class, 'downloadAttachment'])->name('occurrences.attachment');

    Route::resource('occurrence-types', OccurrenceTypeController::class)->except(['show']);
    Route::resource('document-types', DocumentTypeController::class)->except(['show']);
    Route::resource('checklist-templates', ChecklistTemplateController::class)->except(['show']);
    Route::resource('vendor-service-types', VendorServiceTypeController::class)->except(['show']);

    Route::get('documents', [DocumentFileController::class, 'index'])->name('documents.index');
    Route::post('documents', [DocumentFileController::class, 'store'])->name('documents.store');
    Route::get('documents/{document}/download', [DocumentFileController::class, 'download'])->name('documents.download');
    Route::post('documents/{document}/send-email', [DocumentFileController::class, 'sendEmail'])->name('documents.send-email');
    Route::delete('documents/{document}', [DocumentFileController::class, 'destroy'])->name('documents.destroy');
    Route::post('documents/{document}/restore', [DocumentFileController::class, 'restore'])->name('documents.restore');

    Route::post('contracts/{contract}/tasks', [ContractTaskController::class, 'store'])->name('contract-tasks.store');
    Route::put('contracts/{contract}/tasks/{task}', [ContractTaskController::class, 'update'])->name('contract-tasks.update');
    Route::patch('contracts/{contract}/tasks/{task}/status', [ContractTaskController::class, 'updateStatus'])->name('contract-tasks.status');
    Route::delete('contracts/{contract}/tasks/{task}', [ContractTaskController::class, 'destroy'])->name('contract-tasks.destroy');
    Route::post('contracts/{contract}/tasks/{task}/restore', [ContractTaskController::class, 'restore'])->name('contract-tasks.restore');
    Route::post('contracts/{contract}/regenerate-public-link', [ContractController::class, 'regeneratePublicLink'])->name('contracts.regenerate-public-link');

    Route::post('contracts/{contract}/vendors', [VendorController::class, 'store'])->name('vendors.store');
    Route::put('contracts/{contract}/vendors/{vendor}', [VendorController::class, 'update'])->name('vendors.update');
    Route::delete('contracts/{contract}/vendors/{vendor}', [VendorController::class, 'destroy'])->name('vendors.destroy');
    Route::post('contracts/{contract}/vendors/{vendor}/restore', [VendorController::class, 'restore'])->name('vendors.restore');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::post('audit-logs/{auditLog}/revert', [AuditLogController::class, 'revert'])->name('audit-logs.revert');

    Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');

    Route::get('search', [SearchController::class, 'index'])->name('search.index');
});

Route::prefix('portal/{token}')->name('public.')->group(function () {
    Route::get('/', [PublicContractController::class, 'gate'])->name('gate');
    Route::post('/verify', [PublicContractController::class, 'verify'])
        ->middleware('throttle:public-verify')
        ->name('verify');

    Route::middleware('public.contract')->group(function () {
        Route::get('/contrato', [PublicContractController::class, 'show'])->name('show');
        Route::post('/tasks', [PublicContractController::class, 'storeTask'])->name('tasks.store');
        Route::patch('/tasks/{task}/status', [PublicContractController::class, 'updateTaskStatus'])->name('tasks.status');
        Route::put('/tasks/{task}', [PublicContractController::class, 'updateTask'])->name('tasks.update');
        Route::delete('/tasks/{task}', [PublicContractController::class, 'destroyTask'])->name('tasks.destroy');
        Route::post('/documents', [PublicContractController::class, 'storeDocument'])->name('documents.store');
        Route::get('/documents/{document}/download', [PublicContractController::class, 'downloadDocument'])->name('documents.download');
        Route::post('/vendors', [PublicContractController::class, 'storeVendor'])->name('vendors.store');
        Route::put('/vendors/{vendor}', [PublicContractController::class, 'updateVendor'])->name('vendors.update');
        Route::delete('/vendors/{vendor}', [PublicContractController::class, 'destroyVendor'])->name('vendors.destroy');
    });
});

require __DIR__.'/auth.php';
