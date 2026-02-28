<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TournamentApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/tournaments/sync-all', [TournamentApiController::class, 'getSyncData']);
Route::get('/tournaments/{id}/full-data', [TournamentApiController::class, 'getFullData']);
Route::get('/tournaments/{id}/scoreboard-data', [TournamentApiController::class, 'getScoreboardData']);
Route::post('/matches/{id}/result', [TournamentApiController::class, 'updateMatchResult']);
Route::post('/matches/{id}/update', [TournamentApiController::class, 'updateMatch']);

// Legacy Scoreboard Sync Support
Route::post('/tournament/sync', function (Request $request) {
    $tournamentId = $request->input('tournament_id');
    if (!$tournamentId) {
        return response()->json(['error' => 'tournament_id is required'], 400);
    }
    return app(TournamentApiController::class)->getFullData($tournamentId);
});
