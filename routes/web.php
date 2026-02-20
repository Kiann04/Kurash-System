<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';

Route::prefix('admin')->group(function () {
    Route::get('players', [PlayerController::class, 'index'])->name('admin.players.index');
    Route::get('players/create', [PlayerController::class, 'create'])->name('admin.players.create');
    Route::post('players', [PlayerController::class, 'store'])->name('admin.players.store');
    Route::get('players/{player}/edit', [PlayerController::class, 'edit'])->name('admin.players.edit');
    Route::put('players/{player}', [PlayerController::class, 'update'])->name('admin.players.update');
    Route::get('players/{player}', [PlayerController::class, 'show'])->name('admin.players.show');
});
