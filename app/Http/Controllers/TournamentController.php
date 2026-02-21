<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\Player;
use App\Models\AgeCategory;
use App\Models\WeightCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        $players = Player::select('id','full_name','gender','birthday','club')
            ->orderBy('full_name')
            ->get()
            ->map(function ($player) {
                $age = Carbon::parse($player->birthday)->age;

                $ageCategory = AgeCategory::where('min_age', '<=', $age)
                    ->where('max_age', '>=', $age)
                    ->first();

                return [
                    'id' => $player->id,
                    'full_name' => $player->full_name,
                    'gender' => $player->gender,
                    'club' => $player->club,
                    'birthday' => $player->birthday,
                    'age' => $age,
                    'age_category_id' => $ageCategory?->id,
                    'age_category' => $ageCategory?->name ?? 'Not Eligible',
                ];
            });

        return Inertia::render('admin/tournament/Create', [
            'players' => $players,
            'weightCategories' => WeightCategory::all(),
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
            'registrations.*.weigh_in_weight' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            $tournament = Tournament::create([
                'name' => $validated['name'],
                'tournament_date' => $validated['tournament_date'],
                'status' => $validated['status'],
            ]);

            foreach ($validated['registrations'] ?? [] as $registration) {
                $player = Player::findOrFail($registration['player_id']);
                $ageCategoryId = $this->getAgeCategoryId($player);

                if (!$ageCategoryId) {
                    throw ValidationException::withMessages([
                        'registrations' => ["{$player->full_name} is not eligible for any age category."],
                    ]);
                }

                $weightCategoryId = $this->getWeightCategoryId(
                    $player->gender,
                    $ageCategoryId,
                    $registration['weigh_in_weight'] ?? null
                );

                $tournament->registrations()->create([
                    'player_id' => $player->id,
                    'age_category_id' => $ageCategoryId,
                    'weight_category_id' => $weightCategoryId,
                    'weigh_in_weight' => $registration['weigh_in_weight'] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('admin.tournaments.index')
            ->with('success', 'Tournament created successfully.');
    }

   public function show(Tournament $tournament)
    {
        // Load registrations with relations
        $tournament->load('registrations.player', 'registrations.ageCategory', 'registrations.weightCategory');

        // Map registrations to player info
        $players = $tournament->registrations->map(function ($registration) {
            $player = $registration->player;

            return [
                'id' => $player->id,
                'full_name' => $player->full_name,
                'gender' => $player->gender,
                'club' => $player->club,
                'age_category' => $registration->ageCategory?->name ?? 'Not Eligible',
                'weigh_in_weight' => $registration->weigh_in_weight, // ✅ include this
                'weight_category_id' => $registration->weight_category_id, // ✅ include ID
            ];
        });

        return Inertia::render('admin/tournament/Show', [
            'tournament' => $tournament,
            'players' => $players,
            'weightCategories' => WeightCategory::all(), // needed to map weight ID -> name
        ]);
    }

    public function edit(Tournament $tournament)
    {
        $players = Player::select('id','full_name','gender','birthday','club')
            ->orderBy('full_name')
            ->get()
            ->map(function ($player) {
                $age = Carbon::parse($player->birthday)->age;
                $ageCategory = AgeCategory::where('min_age', '<=', $age)
                    ->where('max_age', '>=', $age)
                    ->first();

                return [
                    'id' => $player->id,
                    'full_name' => $player->full_name,
                    'gender' => $player->gender,
                    'club' => $player->club,
                    'age_category_id' => $ageCategory?->id,
                    'age_category' => $ageCategory?->name ?? 'Not Eligible',
                ];
            });

        $tournament->load('registrations');

        return Inertia::render('admin/tournament/Edit', [
            'tournament' => $tournament,
            'players' => $players,
            'weightCategories' => WeightCategory::all(),
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
            'registrations.*.weigh_in_weight' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $tournament) {
            $tournament->update([
                'name' => $validated['name'],
                'tournament_date' => $validated['tournament_date'],
                'status' => $validated['status'],
            ]);

            foreach ($validated['registrations'] ?? [] as $reg) {
                $player = Player::findOrFail($reg['player_id']);
                $ageCategoryId = $this->getAgeCategoryId($player);

                if (!$ageCategoryId) {
                    throw ValidationException::withMessages([
                        'registrations' => ["{$player->full_name} is not eligible for any age category."],
                    ]);
                }

                $weightCategoryId = $this->getWeightCategoryId(
                    $player->gender,
                    $ageCategoryId,
                    $reg['weigh_in_weight'] ?? null
                );

                $tournament->registrations()->updateOrCreate(
                    ['player_id' => $player->id],
                    [
                        'age_category_id' => $ageCategoryId,
                        'weight_category_id' => $weightCategoryId,
                        'weigh_in_weight' => $reg['weigh_in_weight'] ?? null,
                    ]
                );
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

    // ✅ Helper: determine weight category id from DB
    private function getWeightCategoryId(?string $gender, ?int $ageCategoryId, ?float $weight): ?int
    {
        if (!$gender || !$ageCategoryId || !$weight) return null;

        $category = WeightCategory::where('gender', $gender)
            ->where('age_category_id', $ageCategoryId)
            ->where('min_weight', '<', $weight)
            ->where('max_weight', '>=', $weight)
            ->first();

        return $category?->id;
    }

    private function getAgeCategoryId(Player $player): ?int
    {
        $age = Carbon::parse($player->birthday)->age;

        $category = AgeCategory::where('min_age', '<=', $age)
            ->where('max_age', '>=', $age)
            ->first();

        return $category?->id;
    }
}
