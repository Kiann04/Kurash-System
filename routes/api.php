<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\TournamentsController;
use App\Http\Controllers\Api\TournamentApiController;

// Public status endpoint
Route::get('/status', [StatusController::class, 'status']);

// Protected API for Scoreboard
Route::middleware(['api.key', 'force.json'])->group(function () {
    // Tournaments list
    Route::get('/tournaments', [TournamentsController::class, 'index']);

    // Documents-style endpoints expected by the Scoreboard
    Route::prefix('documents')->group(function () {
        Route::get('/{id}/full-data', [TournamentApiController::class, 'getFullData']);
        Route::get('/{id}/scoreboard-data', [TournamentApiController::class, 'getScoreboardData']);
    });
    // Tournament-style aliases (same payloads)
    Route::prefix('tournaments')->group(function () {
        Route::get('/{id}/full-data', [TournamentApiController::class, 'getFullData']);
        Route::get('/{id}/scoreboard-data', [TournamentApiController::class, 'getScoreboardData']);
    });

    // Sync endpoint accepts { tournament_id | id | tournamentId }
    Route::post('/tournament/sync', [TournamentsController::class, 'sync']);

    // Result update with progression
    Route::post('/matches/{id}/result', [TournamentApiController::class, 'updateMatchResult']);
});
