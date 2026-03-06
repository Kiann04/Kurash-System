<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\Admin\PlayerController as AdminPlayerController;
use App\Http\Controllers\Admin\PlayerDetailsController as AdminPlayerDetailsController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\BracketController as AdminBracketController;
use App\Http\Controllers\Admin\TournamentController as AdminTournamentController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Public\AthleteController as PublicAthleteController;
use App\Http\Controllers\Public\HomeController as PublicHomeController;
use App\Http\Controllers\Public\RankingController as PublicRankingController;
use App\Http\Controllers\Public\TournamentController as PublicTournamentController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('home', [PublicHomeController::class, 'index'])->name('public.home');
Route::get('public/tournaments', [PublicTournamentController::class, 'index'])->name('public.tournaments.index');
Route::get('public/tournaments/{tournament}', [PublicTournamentController::class, 'show'])->name('public.tournaments.show');
Route::get('public/brackets', [PublicTournamentController::class, 'bracketsIndex'])->name('public.brackets.index');
Route::get('public/brackets/{tournament}', [PublicTournamentController::class, 'tournamentBrackets'])->name('public.brackets.show');
Route::get('public/athletes', [PublicAthleteController::class, 'index'])->name('public.athletes.index');
Route::get('public/rankings', [PublicRankingController::class, 'index'])->name('public.rankings.index');

Route::get('dashboard', [AdminDashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__ . '/settings.php';

Route::prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('players', [AdminPlayerController::class, 'index'])->name('players.index');
        Route::get('players/create', [AdminPlayerController::class, 'create'])->name('players.create');
        Route::post('players', [AdminPlayerController::class, 'store'])->name('players.store');
        Route::get('players/{player}/edit', [AdminPlayerController::class, 'edit'])->name('players.edit');
        Route::put('players/{player}', [AdminPlayerController::class, 'update'])->name('players.update');
        Route::get('players/{player}', [AdminPlayerController::class, 'show'])->name('players.show');
        Route::post('players/{player}/renew', [AdminPlayerController::class, 'renew'])->name('players.renew');

        Route::get('player-details', [AdminPlayerDetailsController::class, 'index'])->name('player-details.index');

        Route::resource('tournaments', AdminTournamentController::class);
        Route::get('tournaments/registration-template/download', [AdminTournamentController::class, 'downloadRegistrationTemplate'])->name('tournaments.download-template');
        Route::post('tournaments/import-analysis', [AdminTournamentController::class, 'importRegistrations'])->name('tournaments.import-analysis');
        Route::get('tournamentDocs', [AdminTournamentController::class, 'docs'])->name('tournaments.docs');
        Route::post('weight-categories', [AdminTournamentController::class, 'storeWeightCategory'])->name('weight-categories.store');
        Route::delete('weight-categories/{weightCategory}', [AdminTournamentController::class, 'destroyWeightCategory'])->name('weight-categories.destroy');

        Route::get('brackets', [AdminBracketController::class, 'index'])->name('brackets.index');
        Route::get('tournaments/{tournament}/brackets', [AdminBracketController::class, 'show'])
            ->name('tournaments.brackets.show');
        Route::post('tournaments/{tournament}/brackets/generate', [AdminBracketController::class, 'generate'])
            ->name('tournaments.brackets.generate');
        Route::post('tournaments/{tournament}/matches/{match}/advance', [AdminBracketController::class, 'advance'])
            ->name('tournaments.matches.advance');
        Route::post('tournaments/{tournament}/matches/{match}/revert', [AdminBracketController::class, 'revert'])
            ->name('tournaments.matches.revert');

        Route::resource('events', AdminEventController::class);
    });
