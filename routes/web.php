<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

// Dashboard routes
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');
Route::post('/dashboard/clear-cache', [DashboardController::class, 'clearCache'])->name('dashboard.clear-cache');
Route::post('/dashboard/restart-queue', [DashboardController::class, 'restartQueue'])->name('dashboard.restart-queue');
Route::post('/dashboard/refresh-metric', [DashboardController::class, 'refreshMetric'])->name('dashboard.refresh-metric');

Route::get('/{any?}', function () {
    return view('dashboard');
})->where('any', '.*');
