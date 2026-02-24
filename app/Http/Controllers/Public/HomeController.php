<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $latestTournaments = Tournament::query()
            ->latest('tournament_date')
            ->take(6)
            ->get(['id', 'name', 'tournament_date', 'status']);

        return Inertia::render('public/Home', [
            'stats' => [
                'total_tournaments' => Tournament::count(),
                'open_tournaments' => Tournament::whereIn('status', ['open', 'ongoing'])->count(),
            ],
            'latest_tournaments' => $latestTournaments,
        ]);
    }
}
