<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TournamentsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $tournaments = Tournament::query()
                ->select(['id', 'name'])
                ->orderBy('id', 'desc')
                ->get()
                ->map(function ($t) {
                    return [
                        'id' => $t->id,
                        'name' => $t->name,
                    ];
                })
                ->values();

            return response()->json($tournaments, 200, ['Content-Type' => 'application/json; charset=utf-8']);
        } catch (\Throwable $e) {
            \Log::error('scoreboard.index error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'error' => 'server_error',
                'message' => 'Unexpected error',
                'details' => null,
            ], 500);
        }
    }

    public function sync(Request $request): JsonResponse
    {
        try {
            $tid = $request->input('tournament_id')
                ?? $request->input('id')
                ?? $request->input('tournamentId');

            if (!is_numeric($tid)) {
                return response()->json([
                    'success' => false,
                    'error' => 'validation_error',
                    'message' => 'A numeric tournament_id is required',
                    'details' => ['tournament_id' => ['required']],
                ], 422);
            }

            $tournament = Tournament::with([
                'brackets.ageCategory',
                'brackets.weightCategory',
                'brackets.matches.playerOne',
                'brackets.matches.playerTwo',
            ])->find((int) $tid);

            if (!$tournament) {
                return response()->json([
                    'success' => false,
                    'error' => 'not_found',
                    'message' => 'Tournament not found',
                    'details' => null,
                ], 404);
            }

            $payload = [
                'tournament' => [
                    'id' => $tournament->id,
                    'name' => $tournament->name,
                    'date' => $tournament->tournament_date?->format('Y-m-d'),
                    'status' => $tournament->status,
                ],
                'brackets' => $tournament->brackets->map(function ($bracket) {
                    $name = trim(
                        ($bracket->ageCategory?->name ?? '') . ' ' .
                        ($bracket->weightCategory?->name ?? '') . ' ' .
                        ($bracket->gender ?? '')
                    );
                    $matches = $bracket->matches
                        ->sortBy('global_match_order')
                        ->values()
                        ->map(function ($m) use ($bracket) {
                            return [
                                'id' => $m->id,
                                'global_match_order' => $m->global_match_order,
                                'round_number' => $m->round_number,
                                'match_number' => $m->match_number,
                                'player_one_name' => $m->playerOne?->full_name,
                                'player_two_name' => $m->playerTwo?->full_name,
                                'status' => $m->status,
                                'bracket' => (string) $bracket->id,
                            ];
                        });
                    return [
                        'id' => (string) $bracket->id,
                        'name' => $name,
                        'gender' => $bracket->gender ?? 'N/A',
                        'age_category' => $bracket->ageCategory?->name,
                        'weight_category' => $bracket->weightCategory?->name,
                        'matches' => $matches,
                    ];
                })->values(),
            ];

            return response()->json($payload, 200, ['Content-Type' => 'application/json; charset=utf-8']);
        } catch (\Throwable $e) {
            \Log::error('scoreboard.sync error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'error' => 'server_error',
                'message' => 'Unexpected error',
                'details' => null,
            ], 500);
        }
    }
}
