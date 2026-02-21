<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TournamentController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
Route::prefix('admin')
    ->name('admin.')
    ->group(function () {

        // PLAYERS (manual routes — already correct)
        Route::get('players', [PlayerController::class, 'index'])->name('players.index');
        Route::get('players/create', [PlayerController::class, 'create'])->name('players.create');
        Route::post('players', [PlayerController::class, 'store'])->name('players.store');
        Route::get('players/{player}/edit', [PlayerController::class, 'edit'])->name('players.edit');
        Route::put('players/{player}', [PlayerController::class, 'update'])->name('players.update');
        Route::get('players/{player}', [PlayerController::class, 'show'])->name('players.show');
        Route::post('players/{player}/renew', [PlayerController::class, 'renew'])->name('players.renew');

        // TOURNAMENTS (resource route with admin prefix)
        Route::resource('tournaments', TournamentController::class);
    });
