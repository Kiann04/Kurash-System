<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\Player;
use App\Models\WeightCategory;
use App\Services\PlayerListImportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
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

    public function docs()
    {
        $tournaments = Tournament::query()
            ->withCount(['registrations', 'brackets'])
            ->latest()
            ->get(['id', 'name', 'tournament_date', 'status']);

        return Inertia::render('admin/tournament/Docs', [
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
            'status' => 'required|in:draft,open',
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

    public function importRegistrations(Request $request, PlayerListImportService $importService)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,docx|max:10240',
            'fallback_category_id' => 'nullable|exists:weight_categories,id',
        ]);

        $analysis = $importService->analyze(
            $validated['file'],
            isset($validated['fallback_category_id']) ? (int) $validated['fallback_category_id'] : null
        );

        return response()->json([
            'message' => 'File analyzed successfully.',
            'analysis' => $analysis,
        ]);
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

    public function storeWeightCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'gender' => 'required|string',
            'age_category_id' => 'required|exists:age_categories,id',
        ]);

        $gender = $this->normalizeGender($validated['gender']);
        if ($gender === null) {
            return response()->json([
                'message' => 'Invalid gender selection.',
            ], 422);
        }

        [$minWeight, $maxWeight] = $this->parseWeightRange($validated['name']);
        if ($maxWeight === null) {
            return response()->json([
                'message' => 'Invalid weight format. Use -60, +70, or 60-66.',
            ], 422);
        }

        if ($minWeight === null && $maxWeight !== null) {
            $previousMax = WeightCategory::query()
                ->where('gender', $gender)
                ->where('age_category_id', $validated['age_category_id'])
                ->where('max_weight', '<', $maxWeight)
                ->max('max_weight');

            $minWeight = $previousMax !== null ? (float) $previousMax : 0.0;
        }

        $existing = WeightCategory::query()
            ->where('name', $validated['name'])
            ->where('gender', $gender)
            ->where('age_category_id', $validated['age_category_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'category' => $this->formatWeightCategory($existing),
                'created' => false,
            ]);
        }

        $category = WeightCategory::create([
            'name' => $validated['name'],
            'gender' => $gender,
            'age_category_id' => $validated['age_category_id'],
            'min_weight' => $minWeight,
            'max_weight' => $maxWeight,
        ]);

        return response()->json([
            'category' => $this->formatWeightCategory($category),
            'created' => true,
        ], 201);
    }

    public function destroyWeightCategory(WeightCategory $weightCategory)
    {
        if ($weightCategory->registrations()->exists()) {
            throw ValidationException::withMessages([
                'weight_category' => 'Cannot delete a weight category that has registrations.',
            ]);
        }

        $weightCategory->delete();

        return response()->json([
            'message' => 'Weight category deleted.',
        ]);
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
            ->map(fn ($category) => $this->formatWeightCategory($category))
            ->values();
    }

    private function formatWeightCategory(WeightCategory $category): array
    {
        $category->loadMissing('ageCategory:id,name');

        return [
            'id' => $category->id,
            'gender' => $category->gender,
            'age_category_id' => $category->age_category_id,
            'age_category_name' => $category->ageCategory?->name ?? '-',
            'name' => $category->name,
        ];
    }

    private function normalizeGender(string $value): ?string
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'male', 'm' => 'Male',
            'female', 'f' => 'Female',
            default => null,
        };
    }

    private function parseWeightRange(string $name): array
    {
        $clean = preg_replace('/\s+/', '', trim($name)) ?? '';

        if (preg_match('/^-(\d+(?:\.\d+)?)$/', $clean, $matches)) {
            return [null, (float) $matches[1]];
        }

        if (preg_match('/^\+(\d+(?:\.\d+)?)$/', $clean, $matches)) {
            return [(float) $matches[1], 999.0];
        }

        if (preg_match('/^(\d+(?:\.\d+)?)\-(\d+(?:\.\d+)?)$/', $clean, $matches)) {
            $min = (float) $matches[1];
            $max = (float) $matches[2];

            if ($max < $min) {
                [$min, $max] = [$max, $min];
            }

            return [$min, $max];
        }

        return [null, null];
    }
}
