<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bracket;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Services\BronzeMatchService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BracketController extends Controller
{
    public function __construct(private BronzeMatchService $bronzeMatchService)
    {
    }

    public function index(): Response
    {
        $tournaments = Tournament::query()
            ->withCount('registrations')
            ->latest()
            ->get(['id', 'name', 'tournament_date', 'status']);

        return Inertia::render('admin/bracket/Index', [
            'tournaments' => $tournaments,
        ]);
    }

    public function generate(Tournament $tournament): RedirectResponse
    {
        $registrations = $tournament->registrations()
            ->with('player:id,gender,club')
            ->whereNotNull('weight_category_id')
            ->get();

        if ($registrations->isEmpty()) {
            return redirect()
                ->route('admin.tournaments.brackets.show', $tournament)
                ->with('error', 'No eligible registrations with weight categories found.');
        }

        $grouped = $registrations->groupBy(function ($registration) {
            return implode('|', [
                $registration->player?->gender,
                $registration->age_category_id,
                $registration->weight_category_id,
            ]);
        });

        $createdBrackets = 0;
        $createdMatches = 0;

        DB::transaction(function () use (
            $tournament,
            $grouped,
            &$createdBrackets,
            &$createdMatches
        ) {
            $tournament->brackets()->delete();

            foreach ($grouped as $key => $group) {
                [$gender, $ageCategoryId, $weightCategoryId] = explode('|', $key);

                $participants = $group
                    ->map(function ($registration) {
                        return [
                            'id' => (int) $registration->player_id,
                            'club' => (string) ($registration->player?->club ?? ''),
                        ];
                    })
                    ->filter(fn ($participant) => !empty($participant['id']))
                    ->values()
                    ->all();

                $playerCount = count($participants);
                if ($playerCount < 2) {
                    continue;
                }

                if ($playerCount <= 5) {
                    $rounds = $this->roundRobinRounds($playerCount);
                    $format = 'round_robin';
                } else {
                    $rounds = $this->singleEliminationRounds($playerCount);
                    $format = 'single_elimination';
                }

                $bracket = Bracket::create([
                    'tournament_id' => $tournament->id,
                    'gender' => $gender,
                    'age_category_id' => (int) $ageCategoryId,
                    'weight_category_id' => (int) $weightCategoryId,
                    'format' => $format,
                    'rounds' => $rounds,
                ]);

                $createdBrackets++;

                if ($format === 'round_robin') {
                    $createdMatches += $this->createRoundRobinMatches(
                        $bracket->id,
                        array_map(fn ($p) => $p['id'], $participants)
                    );
                } else {
                    $createdMatches += $this->createSingleEliminationMatches($bracket->id, $participants);
                }
            }
        });

        return redirect()
            ->route('admin.tournaments.brackets.show', $tournament)
            ->with('success', "Brackets generated: {$createdBrackets}, matches generated: {$createdMatches}.");
    }

    public function show(Tournament $tournament): Response
    {
        // Auto-advance any stuck BYE matches every time the bracket is viewed
        // This ensures existing brackets get fixed automatically
        $this->autoAdvanceAllByes($tournament);

        $categoryParticipants = $tournament->registrations()
            ->with([
                'player:id,gender',
                'ageCategory:id,name',
                'weightCategory:id,name',
            ])
            ->whereNotNull('weight_category_id')
            ->get()
            ->groupBy(function ($registration) {
                return implode('|', [
                    $registration->player?->gender ?? 'unknown',
                    $registration->ageCategory?->name ?? '-',
                    $registration->weightCategory?->name ?? '-',
                ]);
            })
            ->map(function ($group, $key) {
                [$gender, $ageCategory, $weightCategory] = explode('|', $key);

                return [
                    'gender' => $gender,
                    'age_category' => $ageCategory,
                    'weight_category' => $weightCategory,
                    'participant_count' => $group->count(),
                ];
            })
            ->values();

        $brackets = Bracket::query()
            ->where('tournament_id', $tournament->id)
            ->with([
                'ageCategory:id,name',
                'weightCategory:id,name',
                'matches' => function ($query) {
                    $query->with([
                        'playerOne:id,full_name',
                        'playerTwo:id,full_name',
                        'winner:id,full_name',
                    ])->orderBy('round_number')->orderBy('match_number');
                },
            ])
            ->orderBy('gender')
            ->orderBy('age_category_id')
            ->orderBy('weight_category_id')
            ->get();

        return Inertia::render('admin/bracket/Show', [
            'tournament' => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'tournament_date' => $tournament->tournament_date?->toDateString(),
                'status' => $tournament->status,
                'registrations_count' => $tournament->registrations()->count(),
            ],
            'category_participants' => $categoryParticipants,
            'brackets' => $brackets->map(function (Bracket $bracket) {
                $matches = $bracket->matches->where('is_bronze', false);
                $bronzeMatch = $bracket->matches->firstWhere('is_bronze', true);

                $champion = $matches
                    ->where('round_number', $bracket->rounds)
                    ->first()?->winner?->full_name;

                $entrantCount = $matches
                    ->flatMap(function (TournamentMatch $match) {
                        return [$match->player_one_id, $match->player_two_id];
                    })
                    ->filter()
                    ->unique()
                    ->count();

                return [
                    'id' => $bracket->id,
                    'gender' => $bracket->gender,
                    'age_category' => $bracket->ageCategory?->name,
                    'weight_category' => $bracket->weightCategory?->name,
                    'format' => $bracket->format,
                    'rounds' => $bracket->rounds,
                    'entrant_count' => $entrantCount,
                    'champion' => $champion,
                    'awards' => $this->resolveAwards($bracket, $bronzeMatch),
                    'bronze_match' => $bronzeMatch ? [
                        'id' => $bronzeMatch->id,
                        'round_number' => $bronzeMatch->round_number,
                        'match_number' => $bronzeMatch->match_number,
                        'status' => $bronzeMatch->status,
                        'player_one_id' => $bronzeMatch->player_one_id,
                        'player_one' => $bronzeMatch->playerOne?->full_name,
                        'player_two_id' => $bronzeMatch->player_two_id,
                        'player_two' => $bronzeMatch->playerTwo?->full_name,
                        'winner_id' => $bronzeMatch->winner_id,
                        'winner' => $bronzeMatch->winner?->full_name,
                    ] : null,
                    'matches' => $matches->map(function (TournamentMatch $match) {
                        return [
                            'id' => $match->id,
                            'round_number' => $match->round_number,
                            'match_number' => $match->match_number,
                            'status' => $match->status,
                            'player_one_id' => $match->player_one_id,
                            'player_one' => $match->playerOne?->full_name,
                            'player_two_id' => $match->player_two_id,
                            'player_two' => $match->playerTwo?->full_name,
                            'winner_id' => $match->winner_id,
                            'winner' => $match->winner?->full_name,
                        ];
                    }),
                ];
            }),
        ]);
    }

    public function advance(Request $request, Tournament $tournament, TournamentMatch $match): RedirectResponse
    {
        $match->load('bracket');

        if (!$match->bracket || $match->bracket->tournament_id !== $tournament->id) {
            abort(404);
        }

        if ($tournament->status === 'completed') {
            return back()->with('error', 'Cannot advance matches for a completed tournament.');
        }

        // If it's a BYE match, we don't need a winner_id from the request
        $isBye = ($match->player_one_id === null && $match->player_two_id !== null) ||
                 ($match->player_one_id !== null && $match->player_two_id === null);

        if ($isBye) {
            $winnerId = $match->player_one_id ?? $match->player_two_id;
        } else {
            $validated = $request->validate([
                'winner_id' => 'required|exists:players,id',
            ]);

            $winnerId = (int) $validated['winner_id'];
            $allowedWinnerIds = array_filter([
                $match->player_one_id,
                $match->player_two_id,
            ]);

            if (!in_array($winnerId, $allowedWinnerIds, true)) {
                throw ValidationException::withMessages([
                    'winner_id' => ['Winner must be one of the players in this match.'],
                ]);
            }
        }

        DB::transaction(function () use ($match, $winnerId) {
            $match->update([
                'winner_id' => $winnerId,
                'status' => 'completed',
            ]);

            if ($match->bracket?->format === 'single_elimination') {
                $this->advanceWinnerToNextRound($match, $winnerId);
                $this->bronzeMatchService->syncForBracket($match->bracket);
            }
        });

        return back()->with('success', 'Winner updated.');
    }

    private function roundRobinRounds(int $playerCount): int
    {
        return $playerCount % 2 === 0 ? $playerCount - 1 : $playerCount;
    }

    private function singleEliminationRounds(int $playerCount): int
    {
        $size = 1;
        while ($size < $playerCount) {
            $size *= 2;
        }

        return (int) log($size, 2);
    }

    private function createRoundRobinMatches(int $bracketId, array $playerIds): int
    {
        $players = $playerIds;
        if (count($players) % 2 !== 0) {
            $players[] = null;
        }

        $total = count($players);
        $rounds = $total - 1;
        $matchesCreated = 0;

        for ($round = 1; $round <= $rounds; $round++) {
            $matchNumber = 1;

            for ($i = 0; $i < $total / 2; $i++) {
                $playerOne = $players[$i];
                $playerTwo = $players[$total - 1 - $i];

                if ($playerOne !== null && $playerTwo !== null) {
                    TournamentMatch::create([
                        'bracket_id' => $bracketId,
                        'round_number' => $round,
                        'match_number' => $matchNumber++,
                        'player_one_id' => $playerOne,
                        'player_two_id' => $playerTwo,
                        'status' => 'scheduled',
                    ]);

                    $matchesCreated++;
                }
            }

            $fixed = $players[0];
            $rest = array_slice($players, 1);
            $last = array_pop($rest);
            array_unshift($rest, $last);
            $players = array_merge([$fixed], $rest);
        }

        return $matchesCreated;
    }

    private function createSingleEliminationMatches(int $bracketId, array $participants): int
    {
        shuffle($participants);

        $size = 1;
        while ($size < count($participants)) {
            $size *= 2;
        }

        $matchesCreated = 0;
        $firstRoundMatchesCount = (int) ($size / 2);
        $halfSize = (int) ($size / 2);

        [$eastParticipants, $westParticipants] = $this->splitByRegion($participants, $halfSize);

        $eastPairs = $this->buildFirstRoundPairs($eastParticipants, $halfSize);
        $westPairs = $this->buildFirstRoundPairs($westParticipants, $halfSize);
        $firstRoundPairs = array_merge($eastPairs, $westPairs);

        // 1. Create ALL matches for all rounds first (empty)
        // Round 1
        $firstRoundMatches = [];
        for ($i = 0; $i < $firstRoundMatchesCount; $i++) {
            $pair = $firstRoundPairs[$i] ?? [null, null];
            $playerOneId = $pair[0]['id'] ?? null;
            $playerTwoId = $pair[1]['id'] ?? null;

            $match = TournamentMatch::create([
                'bracket_id' => $bracketId,
                'round_number' => 1,
                'match_number' => $i + 1,
                'player_one_id' => $playerOneId,
                'player_two_id' => $playerTwoId,
                'status' => 'scheduled',
            ]);
            $firstRoundMatches[] = $match;
            $matchesCreated++;
        }

        // Subsequent rounds
        $round = 2;
        $matchesInRound = (int) ($firstRoundMatchesCount / 2);
        while ($matchesInRound >= 1) {
            for ($matchNumber = 1; $matchNumber <= $matchesInRound; $matchNumber++) {
                TournamentMatch::create([
                    'bracket_id' => $bracketId,
                    'round_number' => $round,
                    'match_number' => $matchNumber,
                    'player_one_id' => null,
                    'player_two_id' => null,
                    'status' => 'scheduled',
                ]);

                $matchesCreated++;
            }

            $round++;
            $matchesInRound = (int) ($matchesInRound / 2);
        }

        // 2. Now that all matches exist, trigger auto-advancement for Round 1
        foreach ($firstRoundMatches as $match) {
            $p1 = $match->player_one_id;
            $p2 = $match->player_two_id;

            if ($p1 === null && $p2 === null) {
                // 0 players: complete with no winner
                $match->update(['status' => 'completed', 'winner_id' => null]);
                $this->advanceWinnerToNextRound($match, null);
            } elseif ($p1 === null || $p2 === null) {
                // 1 player: BYE
                $winnerId = $p1 ?? $p2;
                $match->update(['status' => 'completed', 'winner_id' => $winnerId]);
                $this->advanceWinnerToNextRound($match, $winnerId);
            }
            // 2 players: leave scheduled
        }

        if ($rounds >= 2) {
            TournamentMatch::create([
                'bracket_id' => $bracketId,
                'round_number' => $rounds + 1,
                'match_number' => 1,
                'player_one_id' => null,
                'player_two_id' => null,
                'status' => 'scheduled',
                'is_bronze' => true,
            ]);
            $matchesCreated++;
        }

        return $matchesCreated;
    }

    private function advanceWinnerToNextRound(TournamentMatch $match, ?int $winnerId): void
    {
        if ($match->is_bronze) {
            return;
        }

        $bracket = $match->bracket;
        if (!$bracket) {
            return;
        }

        $nextRound = $match->round_number + 1;
        if ($nextRound > $bracket->rounds) {
            return;
        }

        $nextMatchNumber = (int) ceil($match->match_number / 2);
        $nextMatch = TournamentMatch::query()
            ->where('bracket_id', $bracket->id)
            ->where('round_number', $nextRound)
            ->where('match_number', $nextMatchNumber)
            ->first();

        if (!$nextMatch) {
            return;
        }

        $slot = $match->match_number % 2 === 1 ? 'player_one_id' : 'player_two_id';
        $nextMatch->update([$slot => $winnerId]);

        // After updating the slot (even if with NULL), check if the next match is now a BYE
        $this->checkAndAdvanceIfBye($nextMatch);
    }

    private function checkAndAdvanceIfBye(TournamentMatch $match): void
    {
        if ($match->is_bronze) {
            return;
        }

        if ($match->bracket?->format !== 'single_elimination') {
            return;
        }

        if ($match->status === 'completed') {
            return;
        }

        $p1 = $match->player_one_id;
        $p2 = $match->player_two_id;

        // A match can be auto-completed if its source matches are already done.
        // For Round 1, source matches don't exist, but they are handled at creation.
        if ($match->round_number === 1) {
            return; 
        }

        $sourceMatch1 = $this->getSourceMatch($match, 1);
        $sourceMatch2 = $this->getSourceMatch($match, 2);

        // If a source match doesn't exist (shouldn't happen in single elimination), 
        // we treat it as completed with no winner.
        $s1Completed = !$sourceMatch1 || $sourceMatch1->status === 'completed';
        $s2Completed = !$sourceMatch2 || $sourceMatch2->status === 'completed';

        if ($s1Completed && $s2Completed) {
            // Both source matches are done. 
            // If both slots are now filled with players, it's a real match - don't auto-advance.
            if ($p1 !== null && $p2 !== null) {
                return;
            }
            
            // If at least one slot is NULL, it's a BYE (or a dead match if both are NULL).
            $winnerId = $p1 ?? $p2;
            
            DB::transaction(function () use ($match, $winnerId) {
                $match->update([
                    'winner_id' => $winnerId,
                    'status' => 'completed',
                ]);
                $this->advanceWinnerToNextRound($match, $winnerId);
            });

            if ($match->bracket) {
                $this->bronzeMatchService->syncForBracket($match->bracket);
            }
        }
    }

    private function getSourceMatch(TournamentMatch $match, int $slot): ?TournamentMatch
    {
        if ($match->round_number <= 1) {
            return null;
        }

        $prevRound = $match->round_number - 1;
        $sourceMatchNumber = ($match->match_number * 2) - ($slot === 1 ? 1 : 0);

        return TournamentMatch::where('bracket_id', $match->bracket_id)
            ->where('round_number', $prevRound)
            ->where('match_number', $sourceMatchNumber)
            ->first();
    }

    private function autoAdvanceAllByes(Tournament $tournament): void
    {
        $matches = TournamentMatch::query()
            ->whereHas('bracket', function ($query) use ($tournament) {
                $query->where('tournament_id', $tournament->id)
                      ->where('format', 'single_elimination');
            })
            ->where('is_bronze', false)
            ->orderBy('round_number')
            ->orderBy('match_number')
            ->get();

        foreach ($matches as $match) {
            if ($match->status === 'completed') {
                // If completed, ensure winner is advanced (sync/heal)
                $this->advanceWinnerToNextRound($match, $match->winner_id);
            } else {
                // If scheduled, check if it can be auto-completed (BYE)
                if ($match->round_number === 1) {
                    // Round 1 special case (handled primarily at generation, but heal here)
                    $p1 = $match->player_one_id;
                    $p2 = $match->player_two_id;
                    if ($p1 === null || $p2 === null) {
                        $winnerId = $p1 ?? $p2;
                        DB::transaction(function () use ($match, $winnerId) {
                            $match->update(['status' => 'completed', 'winner_id' => $winnerId]);
                            $this->advanceWinnerToNextRound($match, $winnerId);
                        });
                    }
                } else {
                    $this->checkAndAdvanceIfBye($match);
                }
            }
        }

        $tournament->brackets()
            ->where('format', 'single_elimination')
            ->get()
            ->each(function (Bracket $bracket) {
                $this->bronzeMatchService->syncForBracket($bracket);
            });
    }

    private function splitByRegion(array $participants, int $halfSize): array
    {
        $clubFrequency = [];
        foreach ($participants as $participant) {
            $club = $participant['club'] ?? '';
            $clubFrequency[$club] = ($clubFrequency[$club] ?? 0) + 1;
        }

        usort($participants, function ($a, $b) use ($clubFrequency) {
            $ca = $clubFrequency[$a['club'] ?? ''] ?? 0;
            $cb = $clubFrequency[$b['club'] ?? ''] ?? 0;
            return $cb <=> $ca;
        });

        $east = [];
        $west = [];

        foreach ($participants as $participant) {
            if (count($east) >= $halfSize) {
                $west[] = $participant;
                continue;
            }

            if (count($west) >= $halfSize) {
                $east[] = $participant;
                continue;
            }

            $eastSameClub = $this->countClubInGroup($east, $participant['club'] ?? '');
            $westSameClub = $this->countClubInGroup($west, $participant['club'] ?? '');

            if (count($east) < count($west)) {
                $east[] = $participant;
            } elseif (count($west) < count($east)) {
                $west[] = $participant;
            } elseif ($eastSameClub <= $westSameClub) {
                $east[] = $participant;
            } else {
                $west[] = $participant;
            }
        }

        return [$east, $west];
    }

    private function countClubInGroup(array $group, string $club): int
    {
        $count = 0;
        foreach ($group as $participant) {
            if (($participant['club'] ?? '') === $club) {
                $count++;
            }
        }

        return $count;
    }

    private function buildFirstRoundPairs(array $participants, int $halfSize): array
    {
        $pairCount = (int) ($halfSize / 2);
        $players = array_values($participants);
        $pairs = [];

        $nullSlots = max(0, $halfSize - count($players));

        while ($nullSlots > 0 && !empty($players)) {
            $pairs[] = [array_shift($players), null];
            $nullSlots--;
        }

        while (count($players) >= 2) {
            $first = array_shift($players);
            $secondIndex = null;

            foreach ($players as $index => $candidate) {
                if (($candidate['club'] ?? '') !== ($first['club'] ?? '')) {
                    $secondIndex = $index;
                    break;
                }
            }

            if ($secondIndex === null) {
                $second = array_shift($players);
            } else {
                $second = $players[$secondIndex];
                unset($players[$secondIndex]);
                $players = array_values($players);
            }

            $pairs[] = [$first, $second];
        }

        if (!empty($players)) {
            $pairs[] = [array_shift($players), null];
        }

        while (count($pairs) < $pairCount) {
            $pairs[] = [null, null];
        }

        return $pairs;
    }

    private function resolveAwards(Bracket $bracket, ?TournamentMatch $bronzeMatch = null): array
    {
        if ($bracket->format !== 'single_elimination') {
            return ['gold' => null, 'silver' => null, 'bronze' => []];
        }

        $final = $bracket->matches->firstWhere('round_number', $bracket->rounds);
        $gold = $final?->winner?->full_name;
        $silver = null;

        if ($final && $final->winner_id) {
            $silver = (int) $final->winner_id === (int) $final->player_one_id
                ? $final->playerTwo?->full_name
                : $final->playerOne?->full_name;
        }

        $bronze = [];
        if ($bronzeMatch) {
            if ($bronzeMatch->winner) {
                $bronze[] = $bronzeMatch->winner->full_name;
            }
        } else {
            $semiRound = $bracket->rounds - 1;
            if ($semiRound >= 1) {
                foreach ($bracket->matches->where('round_number', $semiRound) as $semi) {
                    if (!$semi->winner_id) continue;
                    $loser = (int) $semi->winner_id === (int) $semi->player_one_id
                        ? $semi->playerTwo?->full_name
                        : $semi->playerOne?->full_name;
                    if ($loser) $bronze[] = $loser;
                }
            }
        }

        return [
            'gold' => $gold,
            'silver' => $silver,
            'bronze' => array_values(array_unique($bronze)),
        ];
    }
}
