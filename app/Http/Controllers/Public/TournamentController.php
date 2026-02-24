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
            'brackets.matches',
        ]);

        $categories = $tournament->brackets
            ->map(function ($bracket) {
                return [
                    'id' => $bracket->id,
                    'gender' => $bracket->gender,
                    'age_category' => $bracket->ageCategory?->name ?? '-',
                    'weight_category' => $bracket->weightCategory?->name ?? '-',
                    'format' => $bracket->format,
                    'matches_count' => $bracket->matches->count(),
                    'completed_matches' => $bracket->matches->where('status', 'completed')->count(),
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
