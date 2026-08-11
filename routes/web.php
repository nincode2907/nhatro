<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BillingPeriodController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceExportController;
use App\Http\Controllers\MeterReadingController;
use App\Http\Controllers\PropertyStructureController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::view('/', 'home')->name('home');
    Route::get('/billing-periods', [BillingPeriodController::class, 'index'])
        ->name('billing-periods.index');
    Route::post('/billing-periods', [BillingPeriodController::class, 'store'])
        ->name('billing-periods.store');
    Route::get('/billing-periods/{period}', [BillingPeriodController::class, 'show'])
        ->name('billing-periods.show');
    Route::get('/billing-periods/{period}/invoices', [InvoiceController::class, 'index'])
        ->name('invoices.index');
    Route::get('/billing-periods/{period}/invoices/print', [InvoiceController::class, 'printBatch'])
        ->name('invoices.print-batch');
    Route::get('/billing-periods/{period}/exports/invoices.xlsx', [InvoiceExportController::class, 'xlsx'])
        ->name('invoice-exports.xlsx');
    Route::get('/billing-periods/{period}/exports/invoices.pdf', [InvoiceExportController::class, 'pdfBatch'])
        ->name('invoice-exports.pdf-batch');
    Route::get('/billing-periods/{period}/exports/invoices.docx', [InvoiceExportController::class, 'docxBatch'])
        ->name('invoice-exports.docx-batch');
    Route::get('/billing-periods/{period}/invoices/{invoice}', [InvoiceController::class, 'show'])
        ->name('invoices.show');
    Route::get('/billing-periods/{period}/invoices/{invoice}/print', [InvoiceController::class, 'printSingle'])
        ->name('invoices.print-single');
    Route::get('/billing-periods/{period}/invoices/{invoice}/exports/invoice.pdf', [InvoiceExportController::class, 'pdfSingle'])
        ->name('invoice-exports.pdf-single');
    Route::get('/billing-periods/{period}/invoices/{invoice}/exports/invoice.docx', [InvoiceExportController::class, 'docxSingle'])
        ->name('invoice-exports.docx-single');
    Route::get('/billing-periods/{period}/floors/{floor}/readings', [MeterReadingController::class, 'floor'])
        ->name('meter-readings.floor');
    Route::get('/billing-periods/{period}/floors/{floor}/rooms/{room}/reading', [MeterReadingController::class, 'show'])
        ->name('meter-readings.show');
    Route::put('/billing-periods/{period}/floors/{floor}/rooms/{room}/reading', [MeterReadingController::class, 'update'])
        ->name('meter-readings.update');
    Route::post('/billing-periods/{period}/floors/{floor}/rooms/{room}/reading/skip', [MeterReadingController::class, 'skip'])
        ->name('meter-readings.skip');
    Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::get('/property-structure', [PropertyStructureController::class, 'index'])
        ->name('property-structure.index');
    Route::get('/property-structure/floors/create', [PropertyStructureController::class, 'createFloor'])
        ->name('property-structure.floors.create');
    Route::post('/property-structure/floors', [PropertyStructureController::class, 'storeFloor'])
        ->name('property-structure.floors.store');
    Route::get('/property-structure/floors/{floor}/edit', [PropertyStructureController::class, 'editFloor'])
        ->name('property-structure.floors.edit');
    Route::put('/property-structure/floors/{floor}', [PropertyStructureController::class, 'updateFloor'])
        ->name('property-structure.floors.update');
    Route::get('/property-structure/floors/{floor}/rooms/create', [PropertyStructureController::class, 'createRoom'])
        ->name('property-structure.rooms.create');
    Route::post('/property-structure/floors/{floor}/rooms', [PropertyStructureController::class, 'storeRoom'])
        ->name('property-structure.rooms.store');
    Route::get('/property-structure/rooms/{room}/edit', [PropertyStructureController::class, 'editRoom'])
        ->name('property-structure.rooms.edit');
    Route::put('/property-structure/rooms/{room}', [PropertyStructureController::class, 'updateRoom'])
        ->name('property-structure.rooms.update');
    Route::delete('/property-structure/rooms/{room}', [PropertyStructureController::class, 'destroyRoom'])
        ->name('property-structure.rooms.destroy');
    Route::get('/rooms/{room}/settings', [RoomSettingsController::class, 'edit'])
        ->name('rooms.settings.edit');
    Route::put('/rooms/{room}/settings', [RoomSettingsController::class, 'update'])
        ->name('rooms.settings.update');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
