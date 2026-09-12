<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractItemController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentFileController;
use App\Http\Controllers\InstallmentController;
use App\Http\Controllers\OccurrenceController;
use App\Http\Controllers\OccurrenceTypeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
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
    Route::put('contracts/{contract}/installments/{installment}', [InstallmentController::class, 'update'])->name('installments.update');
    Route::delete('contracts/{contract}/installments/{installment}', [InstallmentController::class, 'destroy'])->name('installments.destroy');
    Route::post('contracts/{contract}/installments/{installment}/restore', [InstallmentController::class, 'restore'])->name('installments.restore');

    Route::post('contracts/{contract}/occurrences', [OccurrenceController::class, 'store'])->name('occurrences.store');
    Route::delete('contracts/{contract}/occurrences/{occurrence}', [OccurrenceController::class, 'destroy'])->name('occurrences.destroy');
    Route::post('contracts/{contract}/occurrences/{occurrence}/restore', [OccurrenceController::class, 'restore'])->name('occurrences.restore');
    Route::get('contracts/{contract}/occurrences/{occurrence}/attachment', [OccurrenceController::class, 'downloadAttachment'])->name('occurrences.attachment');

    Route::resource('occurrence-types', OccurrenceTypeController::class)->except(['show']);

    Route::get('documents', [DocumentFileController::class, 'index'])->name('documents.index');
    Route::post('documents', [DocumentFileController::class, 'store'])->name('documents.store');
    Route::get('documents/{document}/download', [DocumentFileController::class, 'download'])->name('documents.download');
    Route::post('documents/{document}/send-email', [DocumentFileController::class, 'sendEmail'])->name('documents.send-email');
    Route::delete('documents/{document}', [DocumentFileController::class, 'destroy'])->name('documents.destroy');
    Route::post('documents/{document}/restore', [DocumentFileController::class, 'restore'])->name('documents.restore');
});

require __DIR__.'/auth.php';
