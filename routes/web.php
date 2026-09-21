<?php

use App\Http\Controllers\ContractController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SimulatedUserController;
use App\Http\Controllers\TrashedContractController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::post('simulated-user', SimulatedUserController::class)->name('simulated-user.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('contracts/trash', [TrashedContractController::class, 'index'])->name('contracts.trash.index');
    Route::patch('contracts/{contract}/restore', [TrashedContractController::class, 'restore'])
        ->withTrashed()
        ->name('contracts.restore');
    Route::delete('contracts/{contract}/force', [TrashedContractController::class, 'forceDestroy'])
        ->withTrashed()
        ->name('contracts.force-destroy');

    Route::resource('contracts', ContractController::class);
});

require __DIR__.'/settings.php';
