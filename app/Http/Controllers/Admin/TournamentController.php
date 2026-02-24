<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\Player;
use App\Models\WeightCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class TournamentController extends Controller
{
    public function index()
    {
        $tournaments = Tournament::latest()->paginate(10);

        return Inertia::render('admin/tournament/Index', [
            'tournaments' => $tournaments,
        ]);
    }

    public function create()
    {
        $players = $this->mapPlayers();
        $tournamentWeightCategories = $this->mapTournamentWeightCategories();

        return Inertia::render('admin/tournament/Create', [
            'players' => $players,
            'tournamentWeightCategories' => $tournamentWeightCategories,
            'weightCategories' => $tournamentWeightCategories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tournament_date' => 'required|date',
            'status' => 'required|in:draft,open,ongoing,completed',
            'registrations' => 'array',
            'registrations.*.player_id' => 'required|exists:players,id',
            'registrations.*.tournament_weight_category_id' => 'required|exists:weight_categories,id',
        ]);

        DB::transaction(function () use ($validated) {
            $tournament = Tournament::create([
                'name' => $validated['name'],
                'tournament_date' => $validated['tournament_date'],
                'status' => $validated['status'],
            ]);

            $registrations = collect($validated['registrations'] ?? [])
                ->map(fn ($registration) => [
                    'player_id' => (int) $registration['player_id'],
                    'tournament_weight_category_id' => (int) $registration['tournament_weight_category_id'],
                ])
                ->unique(fn ($registration) => $registration['player_id'].'-'.$registration['tournament_weight_category_id'])
                ->values();

            $categoryMap = WeightCategory::query()
                ->whereIn('id', $registrations->pluck('tournament_weight_category_id'))
                ->get(['id', 'age_category_id'])
                ->keyBy('id');

            foreach ($registrations as $registration) {
                $category = $categoryMap->get($registration['tournament_weight_category_id']);
                if (!$category) {
                    continue;
                }

                $tournament->registrations()->create([
                    'player_id' => $registration['player_id'],
                    'age_category_id' => $category->age_category_id,
                    'weight_category_id' => $registration['tournament_weight_category_id'],
                    'weigh_in_weight' => null,
                ]);
            }
        });

        return redirect()
            ->route('admin.tournaments.index')
            ->with('success', 'Tournament created successfully.');
    }

    public function show(Tournament $tournament)
    {
        $tournament->load('registrations.player', 'registrations.ageCategory', 'registrations.weightCategory');

        $players = $tournament->registrations->map(function ($registration) {
            $player = $registration->player;

            return [
                'id' => $player->id,
                'full_name' => $player->full_name,
                'gender' => $player->gender,
                'club' => $player->club,
                'age_category' => $registration->ageCategory?->name ?? 'Not Eligible',
                'weigh_in_weight' => null,
                'weight_category_id' => $registration->weight_category_id,
            ];
        });

        return Inertia::render('admin/tournament/Show', [
            'tournament' => $tournament,
            'players' => $players,
            'weightCategories' => WeightCategory::all(),
        ]);
    }

    public function edit(Tournament $tournament)
    {
        $players = $this->mapPlayers();
        $tournamentWeightCategories = $this->mapTournamentWeightCategories();

        $tournament->load('registrations');

        return Inertia::render('admin/tournament/Edit', [
            'tournament' => $tournament,
            'players' => $players,
            'tournamentWeightCategories' => $tournamentWeightCategories,
            'weightCategories' => $tournamentWeightCategories,
        ]);
    }

    public function update(Request $request, Tournament $tournament)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tournament_date' => 'required|date',
            'status' => 'required|in:draft,open,ongoing,completed',
            'registrations' => 'array',
            'registrations.*.player_id' => 'required|exists:players,id',
            'registrations.*.tournament_weight_category_id' => 'required|exists:weight_categories,id',
        ]);

        DB::transaction(function () use ($validated, $tournament) {
            $tournament->update([
                'name' => $validated['name'],
                'tournament_date' => $validated['tournament_date'],
                'status' => $validated['status'],
            ]);

            $registrations = collect($validated['registrations'] ?? [])
                ->map(fn ($registration) => [
                    'player_id' => (int) $registration['player_id'],
                    'tournament_weight_category_id' => (int) $registration['tournament_weight_category_id'],
                ])
                ->unique(fn ($registration) => $registration['player_id'].'-'.$registration['tournament_weight_category_id'])
                ->values();

            $categoryMap = WeightCategory::query()
                ->whereIn('id', $registrations->pluck('tournament_weight_category_id'))
                ->get(['id', 'age_category_id'])
                ->keyBy('id');

            $tournament->registrations()->delete();

            foreach ($registrations as $registration) {
                $category = $categoryMap->get($registration['tournament_weight_category_id']);
                if (!$category) {
                    continue;
                }

                $tournament->registrations()->create([
                    'player_id' => $registration['player_id'],
                    'age_category_id' => $category->age_category_id,
                    'weight_category_id' => $registration['tournament_weight_category_id'],
                    'weigh_in_weight' => null,
                ]);
            }
        });

        return redirect()
            ->route('admin.tournaments.index')
            ->with('success', 'Tournament updated successfully.');
    }

    public function destroy(Tournament $tournament)
    {
        $tournament->delete();

        return redirect()
            ->route('admin.tournaments.index')
            ->with('success', 'Tournament deleted successfully.');
    }

    private function mapPlayers()
    {
        return Player::query()
            ->select('id', 'full_name', 'gender', 'club')
            ->orderBy('full_name')
            ->get();
    }

    private function mapTournamentWeightCategories()
    {
        return WeightCategory::query()
            ->with('ageCategory:id,name')
            ->orderBy('gender')
            ->orderBy('age_category_id')
            ->orderBy('min_weight')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'gender' => $category->gender,
                    'age_category_id' => $category->age_category_id,
                    'age_category_name' => $category->ageCategory?->name ?? '-',
                    'name' => $category->name,
                ];
            })
            ->values();
    }
}


