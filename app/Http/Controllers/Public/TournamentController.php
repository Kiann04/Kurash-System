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
                $matches = $bracket->matches->where('is_bronze', false);
                $bronzeMatch = $bracket->matches->firstWhere('is_bronze', true);

                $champion = $matches
                    ->where('round_number', $bracket->rounds)
                    ->first()?->winner?->full_name;

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
                    'bronze_match' => $bronzeMatch ? [
                        'id' => $bronzeMatch->id,
                        'round_number' => $bronzeMatch->round_number,
                        'match_number' => $bronzeMatch->match_number,
                        'status' => $bronzeMatch->status,
                        'player_one' => $bronzeMatch->playerOne?->full_name,
                        'player_one_club' => $bronzeMatch->playerOne?->club,
                        'player_one_image' => $bronzeMatch->playerOne?->profile_image,
                        'player_two' => $bronzeMatch->playerTwo?->full_name,
                        'player_two_club' => $bronzeMatch->playerTwo?->club,
                        'player_two_image' => $bronzeMatch->playerTwo?->profile_image,
                        'winner' => $bronzeMatch->winner?->full_name,
                        'player_one_id' => $bronzeMatch->player_one_id,
                        'player_two_id' => $bronzeMatch->player_two_id,
                        'winner_id' => $bronzeMatch->winner_id,
                    ] : null,
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
