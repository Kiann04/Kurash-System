<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Tournament;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $upcomingEvents = Event::query()
            ->where('status', 'published')
            ->where(function ($query) {
                $query->whereDate('start_date', '>=', now()->toDateString())
                    ->orWhereDate('end_date', '>=', now()->toDateString());
            })
            ->orderBy('start_date')
            ->take(4)
            ->get(['id', 'title', 'description', 'location', 'start_date', 'end_date', 'image_path', 'status']);

        $latestTournaments = Tournament::query()
            ->latest('tournament_date')
            ->take(6)
            ->get(['id', 'name', 'tournament_date', 'status']);

        return Inertia::render('public/Home', [
            'stats' => [
                'total_tournaments' => Tournament::count(),
                'open_tournaments' => Tournament::whereIn('status', ['open', 'ongoing'])->count(),
            ],
            'events' => $upcomingEvents,
            'latest_tournaments' => $latestTournaments,
        ]);
    }
}
