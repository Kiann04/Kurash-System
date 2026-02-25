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
        $tournament->load([
            'brackets.ageCategory:id,name',
            'brackets.weightCategory:id,name',
            'brackets.matches' => function ($query) {
                $query->with([
                    'playerOne:id,full_name',
                    'playerTwo:id,full_name',
                    'winner:id,full_name',
                ])->orderBy('round_number')->orderBy('match_number');
            },
        ]);

        $categories = $tournament->brackets
            ->map(function ($bracket) {
                $champion = $bracket->matches
                    ->where('round_number', $bracket->rounds)
                    ->first()?->winner?->full_name;

                return [
                    'id' => $bracket->id,
                    'gender' => $bracket->gender,
                    'age_category' => $bracket->ageCategory?->name ?? '-',
                    'weight_category' => $bracket->weightCategory?->name ?? '-',
                    'format' => $bracket->format,
                    'rounds' => $bracket->rounds,
                    'matches_count' => $bracket->matches->count(),
                    'completed_matches' => $bracket->matches->where('status', 'completed')->count(),
                    'champion' => $champion,
                    'matches' => $bracket->matches->map(function ($match) {
                        return [
                            'id' => $match->id,
                            'round_number' => $match->round_number,
                            'match_number' => $match->match_number,
                            'status' => $match->status,
                            'player_one' => $match->playerOne?->full_name,
                            'player_two' => $match->playerTwo?->full_name,
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

        return Inertia::render('public/TournamentShow', [
            'tournament' => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'tournament_date' => $tournament->tournament_date?->toDateString(),
                'status' => $tournament->status,
            ],
            'categories' => $categories,
        ]);
    }
}
