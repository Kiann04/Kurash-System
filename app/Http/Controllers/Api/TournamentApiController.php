<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchResult;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Services\BronzeMatchService;
use App\Services\MatchSchedulerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class TournamentApiController extends Controller
{
    protected $matchScheduler;
    protected $bronzeMatchService;

    private function validateApiKey(Request $request) { return null; }

    public function __construct(MatchSchedulerService $matchScheduler, BronzeMatchService $bronzeMatchService)
    {
        $this->matchScheduler = $matchScheduler;
        $this->bronzeMatchService = $bronzeMatchService;
    }

    public function health(Request $request) { return response()->json(['status' => 'ok']); }

    public function listTournaments(Request $request)
    {
        try {
            $allowed = ['open', 'ongoing', 'completed'];
            $statusesParam = $request->query('status');
            $statuses = $allowed;
            if ($statusesParam) {
                $requested = collect(explode(',', $statusesParam))
                    ->map(fn ($s) => strtolower(trim($s)))
                    ->filter(fn ($s) => in_array($s, $allowed))
                    ->unique()
                    ->values()
                    ->all();
                if (!empty($requested)) {
                    $statuses = $requested;
                }
            }
            $tournaments = Tournament::query()
                ->select(['id', 'name', 'tournament_date', 'status'])
                ->whereIn('status', $statuses)
                ->orderBy('tournament_date', 'desc')
                ->get()
                ->map(function ($t) {
                    $date = $t->tournament_date instanceof \DateTimeInterface
                        ? $t->tournament_date->format('Y-m-d')
                        : (is_string($t->tournament_date) ? $t->tournament_date : null);
                    return [
                        'id' => $t->id,
                        'name' => $t->name,
                        'date' => $date,
                        'status' => $t->status,
                    ];
                });
            return response()->json(['tournaments' => $tournaments]);
        } catch (\Exception $e) {
            \Log::error('API Error in listTournaments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'server_error',
                'message' => 'Unexpected error',
                'details' => null,
            ], 500);
        }
    }

    /**
     * Get all tournaments with hierarchical data (Brackets -> Matches).
     * 
     * GET /api/tournaments/sync-all
     */
    public function getSyncData()
    {
        if ($resp = $this->validateApiKey(request())) {
            return $resp;
        }
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
                                    'status' => $this->mapStatus($match->status),
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
    public function getFullData(Request $request, $id)
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
                    'error' => 'not_found',
                    'message' => 'Tournament not found',
                    'details' => null,
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
                $derived = $this->deriveFormatAndLabels($bracket);
                return [
                    'id' => $bracket->id,
                    'name' => ($bracket->ageCategory ? $bracket->ageCategory->name : '') . ' ' .
                             ($bracket->weightCategory ? $bracket->weightCategory->name : '') . ' ' .
                             $bracket->gender,
                    'age_category' => $bracket->ageCategory ? $bracket->ageCategory->name : 'Unknown',
                    'weight_category' => $bracket->weightCategory ? $bracket->weightCategory->name : 'Open',
                    'gender' => $bracket->gender,
                    'format' => $derived['format'],
                    'rounds' => $derived['rounds'],
                    'pools' => $derived['pools'],
                    'stage_labels' => $derived['stage_labels'],
                    'matches' => $bracket->matches->map(function ($match) use ($bracket) {
                        return [
                            'id' => $match->id,
                            'round' => $match->round_number,
                            'match_order' => $match->match_number,
                            'status' => $match->status,
                            'global_match_order' => $match->global_match_order,
                            // Backward-compatible color fields
                            'player_red_id' => $match->player_one_id,
                            'player_red_name' => $match->playerOne ? $match->playerOne->full_name : 'TBD',
                            'player_blue_id' => $match->player_two_id,
                            'player_blue_name' => $match->playerTwo ? $match->playerTwo->full_name : 'TBD',
                            'red_score' => $match->result ? $match->result->player_one_score : 0,
                            'blue_score' => $match->result ? $match->result->player_two_score : 0,
                            // Duplicate fields for scoreboard compatibility
                            'player_one_id' => $match->player_one_id,
                            'player_one_name' => $match->playerOne ? $match->playerOne->full_name : 'TBD',
                            'player_two_id' => $match->player_two_id,
                            'player_two_name' => $match->playerTwo ? $match->playerTwo->full_name : 'TBD',
                            'winner_id' => $match->winner_id,
                            'bracket_id' => $bracket->id,
                            'bracket' => [
                                'id' => $bracket->id,
                                'gender' => $bracket->gender,
                                'age_category' => $bracket->ageCategory ? $bracket->ageCategory->name : 'Unknown',
                                'weight_category' => $bracket->weightCategory ? $bracket->weightCategory->name : 'Open',
                            ],
                        ];
                    })->sortBy('global_match_order')->values(),
                ];
            });

            // Flat list of matches for backward compatibility with existing scoreboard logic
            $allMatches = $brackets->flatMap(function($b) { return $b['matches']; })
                                  ->sortBy('global_match_order')
                                  ->values();

            return response()->json([
                'tournament' => [
                    'id' => $tournament->id,
                    'name' => $tournament->name,
                    'date' => $tournament->tournament_date?->format('Y-m-d'),
                    'status' => $tournament->status,
                ],
                'brackets' => $brackets,
                'players' => $players,
                'matches' => $allMatches,
            ]);

        } catch (\Exception $e) {
            \Log::error('API Error in getFullData: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'server_error',
                'message' => 'Unexpected error',
                'details' => null,
            ], 500);
        }
    }

    /**
     * Get scoreboard data with global match order.
     * 
     * GET /api/tournaments/{id}/scoreboard-data
     */
    public function getScoreboardData(Request $request, $id)
    {
        try {
            $bracketFilter = $request->query('bracket_id');
            $cacheKey = "scoreboard-data-{$id}-".($bracketFilter ? $bracketFilter : 'all');
            $payload = Cache::remember($cacheKey, now()->addSeconds(2), function () use ($id, $bracketFilter) {
                $tournament = Tournament::with([
                    'brackets.matches',
                    'brackets.ageCategory',
                    'brackets.weightCategory',
                    'brackets.matches.playerOne',
                    'brackets.matches.playerTwo',
                ])->find($id);

                if (!$tournament) {
                    return null;
                }

                $bracketsCollection = $tournament->brackets;
                if ($bracketFilter) {
                    $bracketsCollection = $bracketsCollection->where('id', (int) $bracketFilter)->values();
                }

                $brackets = $bracketsCollection->map(function ($bracket) {
                    $name = trim(
                        ($bracket->ageCategory?->name ?? '') . ' ' .
                        ($bracket->weightCategory?->name ?? '') . ' ' .
                        ($bracket->gender ?? '')
                    );
                    $derived = $this->deriveFormatAndLabels($bracket);
                    $matches = $bracket->matches->map(function ($match) use ($bracket) {
                        $meta = $this->roundMeta($bracket, $match);
                        $next = $this->nextMatchLink($bracket, $match);
                        return [
                            'id' => $match->id,
                            'bracket_id' => (string) $bracket->id,
                            'global_match_order' => $match->global_match_order,
                            'match_order' => $match->match_number,
                            'round_number' => $match->round_number,
                            'round_name' => $meta['round_name'],
                            'round_order' => $meta['round_order'],
                            'status' => $match->status,
                            'category' => $bracket->weightCategory?->name,
                            'gender' => $bracket->gender ?? null,
                            // Left side is BLUE (player_one), right side is GREEN (player_two)
                            'player_blue' => [
                                'id' => $match->playerOne?->id,
                                'full_name' => $match->playerOne?->full_name ?? 'TBD',
                                'club' => $match->playerOne?->club,
                            ],
                            'player_green' => [
                                'id' => $match->playerTwo?->id,
                                'full_name' => $match->playerTwo?->full_name ?? 'TBD',
                                'club' => $match->playerTwo?->club,
                            ],
                            'side_green' => 'right',
                            'side_blue' => 'left',
                            'next_match_id' => $next['next_match_id'],
                            'next_match_slot' => $next['next_match_slot'],
                            // Data contract fields for local scoreboard (read-only)
                            'player_red_id' => $match->player_two_id,
                            'player_blue_id' => $match->player_one_id,
                            'player_red_name' => $match->playerTwo?->full_name ?? 'TBD',
                            'player_blue_name' => $match->playerOne?->full_name ?? 'TBD',
                            'player_red_team' => $match->playerTwo?->club,
                            'player_blue_team' => $match->playerOne?->club,
                            'winner_to_match_id' => $next['next_match_id'],
                            'winner_to_slot' => $next['winner_to_slot'], // 'player1' | 'player2' | null
                            // Back-compat aliases
                            'player_red' => [
                                'id' => $match->playerTwo?->id,
                                'full_name' => $match->playerTwo?->full_name ?? 'TBD',
                                'club' => $match->playerTwo?->club,
                            ],
                            'player1_name' => $match->playerTwo?->full_name ?? 'TBD',
                            'player2_name' => $match->playerOne?->full_name ?? 'TBD',
                        ];
                    })->sortBy([
                        ['round_order', 'asc'],
                        ['match_number', 'asc'],
                        ['global_match_order', 'asc'],
                    ])->values();
                    return [
                        'id' => (string) $bracket->id,
                        'name' => $name,
                        'gender' => $bracket->gender ?? 'N/A',
                        'age_category' => $bracket->ageCategory?->name,
                        'weight_category' => $bracket->weightCategory?->name,
                        'format' => $derived['format'],
                        'rounds' => $derived['rounds'],
                        'pools' => $derived['pools'],
                        'stage_labels' => $derived['stage_labels'],
                        'matches' => $matches,
                    ];
                });

                return [
                    'tournament' => [
                        'id' => $tournament->id,
                        'name' => $tournament->name,
                        'date' => $tournament->tournament_date?->format('Y-m-d'),
                        'status' => $tournament->status,
                    ],
                    'brackets' => $brackets
                ];
            });

            if (!$payload) {
                return response()->json([
                    'success' => false,
                    'error' => 'not_found',
                    'message' => 'Tournament not found',
                    'details' => null,
                ], 404);
            }

            return response()->json($payload);

        } catch (\Exception $e) {
            \Log::error('API Error in getScoreboardData: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'server_error',
                'message' => 'Unexpected error',
                'details' => null,
            ], 500);
        }
    }

    private function roundMeta($bracket, $match): array
    {
        if (property_exists($match, 'is_bronze') && $match->is_bronze) {
            return ['round_name' => 'Bronze', 'round_order' => 80];
        }
        if ($bracket->format === 'single_elimination' && $bracket->rounds) {
            $r = (int) $bracket->rounds;
            $n = (int) $match->round_number;
            if ($n >= $r) return ['round_name' => 'Final', 'round_order' => 90];
            if ($n === $r - 1) return ['round_name' => 'Semifinal', 'round_order' => 70];
            if ($n === $r - 2) return ['round_name' => 'Quarterfinal', 'round_order' => 60];
            $remaining = $r - $n;
            $map = [
                3 => ['Round of 16', 50],
                4 => ['Round of 32', 40],
                5 => ['Round of 64', 30],
                6 => ['Round of 128', 20],
            ];
            if (isset($map[$remaining])) return ['round_name' => $map[$remaining][0], 'round_order' => $map[$remaining][1]];
            return ['round_name' => 'Preliminary', 'round_order' => 10];
        }
        $rn = $match->round_number ?: 0;
        return ['round_name' => 'Round '.$rn, 'round_order' => max(10, $rn * 10)];
    }

    private function deriveFormatAndLabels($bracket): array
    {
        $format = $bracket->format ?? null;
        $rounds = $bracket->rounds ?? null;
        $pools = $bracket->pools ?? null;
        $stageLabels = null;

        if (!$format) {
            $matchesByRound = $bracket->matches->groupBy('round_number')->map->count()->sortKeys();
            $counts = $matchesByRound->values()->all();
            if (!empty($counts)) {
                $last = end($counts);
                $isDecreasing = true;
                for ($i = 1; $i < count($counts); $i++) {
                    if ($counts[$i] > $counts[$i - 1]) { $isDecreasing = false; break; }
                }
                if ($last === 1 && $isDecreasing) {
                    $format = 'single_elimination';
                } else {
                    $format = 'round_robin';
                }
            }
        }

        if ($format === 'single_elimination') {
            $stageLabels = [];
            $total = (int) ($rounds ?? $bracket->matches->max('round_number') ?? 0);
            if ($total >= 3) { $stageLabels[] = 'Quarterfinals'; }
            if ($total >= 2) { $stageLabels[] = 'Semifinals'; }
            if ($total >= 1) { $stageLabels[] = 'Finals'; }
            $hasBronze = $bracket->matches->where('is_bronze', true)->isNotEmpty();
            if ($hasBronze) { $stageLabels[] = 'Bronze'; }
        }

        if (!$format) {
            $format = 'unknown';
        }

        if ($bracket->format !== $format) {
            $bracket->format = $format;
            $bracket->save();
        }

        return [
            'format' => $format,
            'rounds' => $rounds ?? ($bracket->matches->max('round_number') ?: null),
            'pools' => $pools ?? null,
            'stage_labels' => $stageLabels,
        ];
    }

    private function nextMatchLink($bracket, $match): array
    {
        if ($bracket->format !== 'single_elimination') {
            return ['next_match_id' => null, 'next_match_slot' => null];
        }
        $nextRound = (int) $match->round_number + 1;
        if ($nextRound > (int) $bracket->rounds) {
            return ['next_match_id' => null, 'next_match_slot' => null];
        }
        $nextMatchNumber = (int) ceil($match->match_number / 2);
        $next = $bracket->matches
            ->where('round_number', $nextRound)
            ->where('match_number', $nextMatchNumber)
            ->first();
        if (!$next) {
            return ['next_match_id' => null, 'next_match_slot' => null];
        }
        // For next match: left = BLUE (player_one) => 'player1', right = GREEN (player_two) => 'player2'
        $isLeft = ($match->match_number % 2 === 1);
        $slot = $isLeft ? 'blue' : 'green';
        $winnerToSlot = $isLeft ? 'player1' : 'player2';
        return ['next_match_id' => $next->id, 'next_match_slot' => $slot, 'winner_to_slot' => $winnerToSlot];
    }

    /**
     * Update match details from scoreboard.
     * 
     * POST /api/matches/{id}/update
     */
    public function updateMatch(Request $request, $id)
    {
        if ($resp = $this->validateApiKey($request)) {
            return $resp;
        }
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
        $data = $request->all();
        $winnerSide = isset($data['winner']) ? strtolower((string) $data['winner']) : null;
        $winnerId = $data['winner_id'] ?? null;
        $redScore = isset($data['red_score']) ? (int) $data['red_score'] : 0;
        $blueScore = isset($data['blue_score']) ? (int) $data['blue_score'] : 0;

        try {
            DB::beginTransaction();
            $match = TournamentMatch::with(['bracket', 'playerOne', 'playerTwo'])->find($id);
            if (!$match) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'error' => 'not_found',
                    'message' => 'Match not found',
                    'details' => null,
                ], 404);
            }

            // Left = BLUE (player_one), Right = GREEN (player_two)
            if ($winnerSide === 'green') {
                $winnerId = $match->player_two_id;
            } elseif ($winnerSide === 'blue') {
                $winnerId = $match->player_one_id;
            }

            if (!$winnerId || !in_array((int) $winnerId, [(int) $match->player_one_id, (int) $match->player_two_id], true)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'error' => 'validation_error',
                    'message' => 'winner must be "green" or "blue" or a valid winner_id',
                    'details' => null,
                ], 422);
            }

            $match->winner_id = $winnerId;
            $match->status = 'completed';
            $match->save();

            MatchResult::updateOrCreate(
                ['match_id' => $match->id],
                [
                    'player_one_score' => $redScore,
                    'player_two_score' => $blueScore,
                    'winning_player_id' => $winnerId,
                    'method' => 'points',
                ]
            );

            $nextMatch = null;
            if ($match->bracket && $match->bracket->format === 'single_elimination') {
                $nextRound = (int) $match->round_number + 1;
                if ($nextRound <= (int) $match->bracket->rounds) {
                    $nextMatchNumber = (int) ceil($match->match_number / 2);
                    // Odd feeds into left slot of next match (player_one_id = BLUE)
                    $slotField = ($match->match_number % 2 === 1) ? 'player_one_id' : 'player_two_id';
                    $nextMatch = TournamentMatch::query()
                        ->where('bracket_id', $match->bracket_id)
                        ->where('round_number', $nextRound)
                        ->where('match_number', $nextMatchNumber)
                        ->first();
                    if ($nextMatch) {
                        $nextMatch->update([$slotField => $winnerId]);
                    }
                }
            }

            DB::commit();

            if ($match->bracket) {
                $this->bronzeMatchService->syncForBracket($match->bracket);
            }

            $bracket = $match->bracket;
            $meta = $bracket ? $this->roundMeta($bracket, $match) : ['round_name' => null, 'round_order' => 0];
            $respMatch = [
                'id' => $match->id,
                'bracket_id' => (string) $match->bracket_id,
                'global_match_order' => $match->global_match_order,
                'round_number' => $match->round_number,
                'round_name' => $meta['round_name'],
                'round_order' => $meta['round_order'],
                'match_number' => $match->match_number,
                'status' => $match->status,
                // Left = BLUE, Right = GREEN
                'player_blue' => [
                    'id' => $match->playerOne?->id,
                    'full_name' => $match->playerOne?->full_name ?? 'TBD',
                    'club' => $match->playerOne?->club,
                ],
                'player_green' => [
                    'id' => $match->playerTwo?->id,
                    'full_name' => $match->playerTwo?->full_name ?? 'TBD',
                    'club' => $match->playerTwo?->club,
                ],
                'side_green' => 'right',
                'side_blue' => 'left',
            ];

            $respNext = null;
            if ($nextMatch) {
                $nmeta = $this->roundMeta($bracket, $nextMatch);
                $respNext = [
                    'id' => $nextMatch->id,
                    'bracket_id' => (string) $nextMatch->bracket_id,
                    'global_match_order' => $nextMatch->global_match_order,
                    'round_number' => $nextMatch->round_number,
                    'round_name' => $nmeta['round_name'],
                    'round_order' => $nmeta['round_order'],
                    'match_number' => $nextMatch->match_number,
                    'status' => $nextMatch->status,
                    // Left = BLUE (player_one), Right = GREEN (player_two)
                    'player_blue' => [
                        'id' => $nextMatch->player_one_id,
                        'full_name' => optional($nextMatch->playerOne)->full_name ?? 'TBD',
                        'club' => optional($nextMatch->playerOne)->club,
                    ],
                    'player_green' => [
                        'id' => $nextMatch->player_two_id,
                        'full_name' => optional($nextMatch->playerTwo)->full_name ?? 'TBD',
                        'club' => optional($nextMatch->playerTwo)->club,
                    ],
                    'side_green' => 'right',
                    'side_blue' => 'left',
                ];
            }

            return response()->json([
                'success' => true,
                'match' => $respMatch,
                'next_match' => $respNext,
                'bracket_id' => $match->bracket_id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'server_error',
                'message' => 'Unexpected error',
                'details' => null,
            ], 500);
        }
    }

    private function mapStatus($status)
    {
        if ($status === 'scheduled') return 'pending';
        if ($status === 'ongoing') return 'current';
        if ($status === 'completed') return 'completed';
        return $status;
    }
}
