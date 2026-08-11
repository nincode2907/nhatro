<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BillingPeriodController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MeterReadingController;
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
    Route::get('/billing-periods/{period}/invoices/{invoice}', [InvoiceController::class, 'show'])
        ->name('invoices.show');
    Route::get('/billing-periods/{period}/floors/{floor}/readings', [MeterReadingController::class, 'floor'])
        ->name('meter-readings.floor');
    Route::get('/billing-periods/{period}/floors/{floor}/rooms/{room}/reading', [MeterReadingController::class, 'show'])
        ->name('meter-readings.show');
    Route::put('/billing-periods/{period}/floors/{floor}/rooms/{room}/reading', [MeterReadingController::class, 'update'])
        ->name('meter-readings.update');
    Route::post('/billing-periods/{period}/floors/{floor}/rooms/{room}/reading/skip', [MeterReadingController::class, 'skip'])
        ->name('meter-readings.skip');
    Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::get('/rooms/{room}/settings', [RoomSettingsController::class, 'edit'])
        ->name('rooms.settings.edit');
    Route::put('/rooms/{room}/settings', [RoomSettingsController::class, 'update'])
        ->name('rooms.settings.update');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
