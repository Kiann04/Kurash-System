<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Tournament;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $today = now()->toDateString();

        $upcomingEvents = Event::query()
            ->whereIn('status', ['published', 'Published'])
            ->where(function ($query) use ($today) {
                $query->whereDate('start_date', '>=', $today)
                    ->orWhere(function ($q) use ($today) {
                        $q->whereNotNull('end_date')
                          ->whereDate('end_date', '>=', $today);
                    });
            })
            ->orderBy('start_date')
            ->take(4)
            ->get(['id', 'title', 'description', 'location', 'start_date', 'end_date', 'image_path', 'status'])
            ->map(function ($event) {
                $path = $event->image_path;
                if ($path) {
                    if (str_starts_with($path, 'http')) {
                        return $event;
                    }
                    $relative = ltrim(Str::after(ltrim($path, '/'), 'storage/'), '/');
                    $event->image_path = route('media', ['path' => $relative]);
                }
                return $event;
            });

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
