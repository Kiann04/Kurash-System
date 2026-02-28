<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchResult;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Services\MatchSchedulerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TournamentApiController extends Controller
{
    protected $matchScheduler;

    public function __construct(MatchSchedulerService $matchScheduler)
    {
        $this->matchScheduler = $matchScheduler;
    }

    /**
     * Get all tournaments with hierarchical data (Brackets -> Matches).
     * 
     * GET /api/tournaments/sync-all
     */
    public function getSyncData()
    {
        try {
            $tournaments = Tournament::with([
                'brackets.ageCategory',
                'brackets.weightCategory',
                'brackets.matches.playerOne',
                'brackets.matches.playerTwo',
                'brackets.matches.result',
            ])
            ->whereIn('status', ['open', 'ongoing', 'completed']) // Exclude draft
            ->orderBy('tournament_date', 'desc')
            ->get();

            $data = $tournaments->map(function ($tournament) {
                return [
                    'id' => $tournament->id,
                    'name' => $tournament->name,
                    'date' => $tournament->tournament_date->format('Y-m-d'),
                    'status' => $tournament->status,
                    'brackets' => $tournament->brackets->map(function ($bracket) {
                        return [
                            'id' => $bracket->id,
                            'name' => ($bracket->ageCategory ? $bracket->ageCategory->name : '') . ' ' . 
                                     ($bracket->weightCategory ? $bracket->weightCategory->name : '') . ' ' . 
                                     $bracket->gender,
                            'format' => $bracket->format,
                            'gender' => $bracket->gender,
                            'matches' => $bracket->matches->map(function ($match) {
                                return [
                                    'id' => $match->id,
                                    'round' => $match->round_number,
                                    'match_order' => $match->match_number,
                                    'global_order' => $match->global_match_order,
                                    'player_red_id' => $match->player_one_id,
                                    'player_red_name' => $match->playerOne ? $match->playerOne->full_name : 'TBD',
                                    'player_blue_id' => $match->player_two_id,
                                    'player_blue_name' => $match->playerTwo ? $match->playerTwo->full_name : 'TBD',
                                    'winner_id' => $match->winner_id,
                                    'status' => $match->status,
                                    'red_score' => $match->result ? $match->result->player_one_score : 0,
                                    'blue_score' => $match->result ? $match->result->player_two_score : 0,
                                ];
                            })->sortBy('global_order')->values()
                        ];
                    })
                ];
            });

            return response()->json([
                'success' => true,
                'tournaments' => $data
            ]);

        } catch (\Exception $e) {
            \Log::error('API Error in getSyncData: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch hierarchical sync data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get full tournament data for synchronization.
     *
     * GET /api/tournaments/{id}/full-data
     */
    public function getFullData($id)
    {
        try {
            $tournament = Tournament::with([
                'registrations.player',
                'brackets.matches.result',
                'brackets.weightCategory',
                'brackets.ageCategory',
                'brackets.matches.playerOne',
                'brackets.matches.playerTwo',
            ])->find($id);

            if (!$tournament) {
                return response()->json([
                    'success' => false,
                    'error' => 'Tournament not found with ID: ' . $id
                ], 404);
            }

            // Group players for reference
            $players = $tournament->registrations->map(function ($reg) {
                if (!$reg->player) return null;
                return [
                    'id' => $reg->player->id,
                    'full_name' => $reg->player->full_name,
                    'club' => $reg->player->club,
                    'gender' => $reg->player->gender,
                    'category_id' => $reg->weight_category_id,
                    'registration_id' => $reg->id,
                ];
            })->filter()->values();

            // Structure data by bracket as requested
            $brackets = $tournament->brackets->map(function ($bracket) {
                return [
                    'id' => $bracket->id,
                    'name' => ($bracket->ageCategory ? $bracket->ageCategory->name : '') . ' ' . 
                             ($bracket->weightCategory ? $bracket->weightCategory->name : '') . ' ' . 
                             $bracket->gender,
                    'age_category' => $bracket->ageCategory ? $bracket->ageCategory->name : 'Unknown',
                    'weight_category' => $bracket->weightCategory ? $bracket->weightCategory->name : 'Open',
                    'gender' => $bracket->gender,
                    'matches' => $bracket->matches->map(function ($match) {
                        return [
                            'id' => $match->id,
                            'round' => $match->round_number,
                            'match_order' => $match->match_number,
                            'player_red_id' => $match->player_one_id,
                            'player_red_name' => $match->playerOne ? $match->playerOne->full_name : 'TBD',
                            'player_blue_id' => $match->player_two_id,
                            'player_blue_name' => $match->playerTwo ? $match->playerTwo->full_name : 'TBD',
                            'winner_id' => $match->winner_id,
                            'status' => $match->status,
                            'red_score' => $match->result ? $match->result->player_one_score : 0,
                            'blue_score' => $match->result ? $match->result->player_two_score : 0,
                            'global_match_order' => $match->global_match_order,
                        ];
                    })->sortBy('global_match_order')->values()
                ];
            });

            // Flat list of matches for backward compatibility with existing scoreboard logic
            $allMatches = $brackets->flatMap(function($b) { return $b['matches']; })
                                  ->sortBy('global_match_order')
                                  ->values();

            return response()->json([
                'success' => true,
                'count' => $allMatches->count(),
                'tournament' => $tournament->only(['id', 'name', 'tournament_date', 'status']),
                'brackets' => $brackets,
                'players' => $players,
                'matches' => $allMatches, // Keep flat list so existing code doesn't break
            ]);

        } catch (\Exception $e) {
            \Log::error('API Error in getFullData: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch tournament data',
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Get scoreboard data with global match order.
     * 
     * GET /api/tournaments/{id}/scoreboard-data
     */
    public function getScoreboardData($id)
    {
        try {
            $tournament = Tournament::with([
                'brackets.matches',
                'brackets.ageCategory',
                'brackets.weightCategory',
                'brackets.matches.playerOne',
                'brackets.matches.playerTwo',
            ])->find($id);

            if (!$tournament) {
                return response()->json(['error' => 'Tournament not found with ID: ' . $id], 404);
            }

            $brackets = $tournament->brackets->map(function ($bracket) {
                return [
                    'id' => $bracket->id,
                    'age_category' => $bracket->ageCategory ? $bracket->ageCategory->name : 'Unknown',
                    'gender' => $bracket->gender,
                    'weight_category' => $bracket->weightCategory ? $bracket->weightCategory->name : 'Open',
                    'participants' => [],
                    'matches' => $bracket->matches->map(function ($match) {
                        return [
                            'id' => $match->id,
                            'round_number' => $match->round_number,
                            'bracket_id' => $match->bracket_id,
                            'player_red' => $match->playerOne,
                            'player_blue' => $match->playerTwo,
                            'global_match_order' => $match->global_match_order,
                            'status' => $match->status,
                        ];
                    })->sortBy('global_match_order')->values()
                ];
            });

            return response()->json([
                'tournament' => $tournament->only(['id', 'name', 'tournament_date', 'status']),
                'brackets' => $brackets
            ]);

        } catch (\Exception $e) {
            \Log::error('API Error in getScoreboardData: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch scoreboard data',
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Update match details from scoreboard.
     * 
     * POST /api/matches/{id}/update
     */
    public function updateMatch(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'player_one_id' => 'nullable|exists:players,id',
            'player_two_id' => 'nullable|exists:players,id',
            'global_match_order' => 'nullable|integer',
            'round_number' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();
            
            $match = TournamentMatch::findOrFail($id);
            $match->update($request->only(['player_one_id', 'player_two_id', 'global_match_order', 'round_number']));

            DB::commit();

            return response()->json([
                'message' => 'Match updated successfully',
                'match' => $match->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to update match',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update match result.
     *
     * POST /api/matches/{id}/result
     */
    public function updateMatchResult(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'winner_id' => 'required|exists:players,id',
            'red_score' => 'required|integer|min:0',
            'blue_score' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $match = TournamentMatch::findOrFail($id);
            $match->winner_id = $request->winner_id;
            $match->status = 'completed';
            $match->save();

            MatchResult::updateOrCreate(
                ['match_id' => $id],
                [
                    'player_one_score' => $request->red_score,
                    'player_two_score' => $request->blue_score,
                    'winning_player_id' => $request->winner_id,
                    'method' => 'points', // Default method
                ]
            );

            DB::commit();

            return response()->json([
                'message' => 'Match result updated successfully',
                'match' => $match->fresh(['result'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to update match result',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
