<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use Inertia\Inertia;
use Inertia\Response;

class TournamentController extends Controller
{
    public function index(): Response
    {
        $tournaments = Tournament::query()
            ->latest('tournament_date')
            ->paginate(12)
            ->through(function ($tournament) {
                return [
                    'id' => $tournament->id,
                    'name' => $tournament->name,
                    'tournament_date' => $tournament->tournament_date?->toDateString(),
                    'status' => $tournament->status,
                ];
            });

        return Inertia::render('public/Tournaments', [
            'tournaments' => $tournaments,
        ]);
    }

    public function show(Tournament $tournament): Response
    {
        return Inertia::render('public/TournamentShow', $this->getTournamentData($tournament));
    }

    public function bracketsIndex(): Response
    {
        $tournaments = Tournament::query()
            ->latest('tournament_date')
            ->paginate(12)
            ->through(function ($tournament) {
                return [
                    'id' => $tournament->id,
                    'name' => $tournament->name,
                    'tournament_date' => $tournament->tournament_date?->toDateString(),
                    'status' => $tournament->status,
                    'brackets_count' => $tournament->brackets()->count(),
                ];
            });

        return Inertia::render('public/BracketsIndex', [
            'tournaments' => $tournaments,
        ]);
    }

    public function tournamentBrackets(Tournament $tournament): Response
    {
        return Inertia::render('public/Brackets', $this->getTournamentData($tournament));
    }

    private function getTournamentData(Tournament $tournament): array
    {
        $tournament->load([
            'brackets.ageCategory:id,name',
            'brackets.weightCategory:id,name',
            'brackets.matches' => function ($query) {
                $query->with([
                    'playerOne:id,full_name,club,profile_image',
                    'playerTwo:id,full_name,club,profile_image',
                    'winner:id,full_name',
                ])->orderBy('round_number')->orderBy('match_number');
            },
        ]);

        $categories = $tournament->brackets
            ->map(function ($bracket) {
                $matches = $bracket->matches;
                $bronzeMatch = null;

                $champion = $matches
                    ->where('round_number', $bracket->rounds)
                    ->first()?->winner?->full_name;

                $final = $matches->firstWhere('round_number', $bracket->rounds);
                $silver = null;
                if ($final && $final->winner_id) {
                    $silver = (int) $final->winner_id === (int) $final->player_one_id
                        ? $final->playerTwo?->full_name
                        : $final->playerOne?->full_name;
                }

                $bronze = [];
                $semiRound = $bracket->rounds - 1;
                if ($bracket->format === 'single_elimination' && $semiRound >= 1) {
                    foreach ($matches->where('round_number', $semiRound) as $semi) {
                        if (!$semi->winner_id) continue;
                        $loser = (int) $semi->winner_id === (int) $semi->player_one_id
                            ? $semi->playerTwo?->full_name
                            : $semi->playerOne?->full_name;
                        if ($loser) $bronze[] = $loser;
                    }
                }

                $entrantCount = $matches
                    ->flatMap(function ($match) {
                        return [$match->player_one_id, $match->player_two_id];
                    })
                    ->filter()
                    ->unique()
                    ->count();

                return [
                    'id' => $bracket->id,
                    'gender' => $bracket->gender,
                    'age_category' => $bracket->ageCategory?->name ?? '-',
                    'weight_category' => $bracket->weightCategory?->name ?? '-',
                    'format' => $bracket->format,
                    'rounds' => $bracket->rounds,
                    'entrant_count' => $entrantCount,
                    'matches_count' => $matches->count(),
                    'completed_matches' => $matches->where('status', 'completed')->count(),
                    'champion' => $champion,
                    'awards' => [
                        'gold' => $champion,
                        'silver' => $silver,
                        'bronze' => array_values(array_unique($bronze)),
                    ],
                    'bronze_match' => null,
                    'matches' => $matches->map(function ($match) {
                        return [
                            'id' => $match->id,
                            'round_number' => $match->round_number,
                            'match_number' => $match->match_number,
                            'status' => $match->status,
                            'player_one' => $match->playerOne?->full_name,
                            'player_one_club' => $match->playerOne?->club,
                            'player_one_image' => $match->playerOne?->profile_image,
                            'player_two' => $match->playerTwo?->full_name,
                            'player_two_club' => $match->playerTwo?->club,
                            'player_two_image' => $match->playerTwo?->profile_image,
                            'winner' => $match->winner?->full_name,
                            'player_one_id' => $match->player_one_id,
                            'player_two_id' => $match->player_two_id,
                            'winner_id' => $match->winner_id,
                        ];
                    }),
                ];
            })
            ->sortBy(['gender', 'age_category', 'weight_category'])
            ->values();

        return [
            'tournament' => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'tournament_date' => $tournament->tournament_date?->toDateString(),
                'status' => $tournament->status,
            ],
            'categories' => $categories,
        ];
    }
}
