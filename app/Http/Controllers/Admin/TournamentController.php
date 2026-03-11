<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\Player;
use App\Models\WeightCategory;
use App\Services\PlayerListImportService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TournamentController extends Controller
{
    /**
     * Display a listing of the tournaments.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $tournaments = Tournament::latest()->paginate(10);

        return Inertia::render('admin/tournament/Index', [
            'tournaments' => $tournaments,
        ]);
    }

    /**
     * Display the tournament documentation page.
     *
     * @return \Inertia\Response
     */
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

    /**
     * Show the form for creating a new tournament.
     *
     * @return \Inertia\Response
     */
    public function create(Request $request)
    {
        $players = $this->mapPlayers();
        $tournamentWeightCategories = $this->mapTournamentWeightCategories();

        $initial = [
            'name' => (string) $request->query('name', ''),
            'location' => $request->query('location') ?: null,
            'tournament_date' => (string) $request->query('date', ''),
            'status' => in_array($request->query('status'), ['draft', 'open']) ? $request->query('status') : 'draft',
        ];

        return Inertia::render('admin/tournament/Create', [
            'players' => $players,
            'tournamentWeightCategories' => $tournamentWeightCategories,
            'weightCategories' => $tournamentWeightCategories,
            'initial' => $initial,
        ]);
    }

    /**
     * Store a newly created tournament in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'tournament_date' => 'required|date',
            'status' => 'required|in:draft,open',
            'registrations' => 'array',
            'registrations.*.player_id' => 'required|exists:players,id',
            'registrations.*.tournament_weight_category_id' => 'required|exists:weight_categories,id',
        ]);

        DB::transaction(function () use ($validated) {
            $tournament = Tournament::create([
                'name' => $validated['name'],
                'location' => $validated['location'] ?? null,
                'tournament_date' => $validated['tournament_date'],
                'status' => $validated['status'],
            ]);

            $registrations = collect($validated['registrations'] ?? [])
                ->map(fn($registration) => [
                    'player_id' => (int) $registration['player_id'],
                    'tournament_weight_category_id' => (int) $registration['tournament_weight_category_id'],
                ])
                ->unique(fn($registration) => $registration['player_id'] . '-' . $registration['tournament_weight_category_id'])
                ->values();

            $playerIds = $registrations->pluck('player_id')->unique();
            $playersMap = Player::whereIn('id', $playerIds)->get(['id', 'gender', 'full_name'])->keyBy('id');
            $invalidPlayers = $playersMap
                ->where(function ($query) {
                    $query->where('status', 'inactive')
                        ->orWhere(function ($q) {
                            $q->whereNotNull('membership_expires_at')
                                ->where('membership_expires_at', '<', now());
                        });
                })
                ->pluck('full_name');

            if ($invalidPlayers->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'registrations' => 'The following players have expired memberships or are inactive: ' . $invalidPlayers->implode(', '),
                ]);
            }

            $categoryMap = WeightCategory::query()
                ->whereIn('id', $registrations->pluck('tournament_weight_category_id'))
                ->get(['id', 'age_category_id', 'gender'])
                ->keyBy('id');

            $genderMismatches = collect($registrations)->filter(function ($registration) use ($playersMap, $categoryMap) {
                $player = $playersMap->get($registration['player_id']);
                $category = $categoryMap->get($registration['tournament_weight_category_id']);
                if (!$player || !$category) return false;
                $pg = strtolower($player->gender);
                $cg = strtolower($category->gender);
                return (($cg === 'male' || $cg === 'm') && !($pg === 'male' || $pg === 'm')) ||
                    (($cg === 'female' || $cg === 'f') && !($pg === 'female' || $pg === 'f'));
            })->map(fn($reg) => $playersMap->get($reg['player_id'])?->full_name)->filter()->values();

            if ($genderMismatches->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'registrations' => 'The following players do not match the selected category gender: ' . $genderMismatches->implode(', '),
                ]);
            }

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

    /**
     * Import registrations from a file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Services\PlayerListImportService  $importService
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Download the registration template file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadRegistrationTemplate(Request $request)
    {
        $format = $request->query('format', 'csv');

        if ($format === 'docx') {
            if (!class_exists(\PhpOffice\PhpWord\PhpWord::class)) {
                Log::warning('PHPWord not available. Falling back to CSV for registration template.');
                $format = 'csv';
            }
        }

        if ($format === 'docx') {
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $section = $phpWord->addSection();

            $table = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '999999',
                'cellMargin' => 50
            ]);

            $table->addRow();
            $table->addCell(500)->addText('No', ['bold' => true]);
            $table->addCell(3000)->addText('Full Name', ['bold' => true]);
            $table->addCell(1500)->addText('Gender', ['bold' => true]);
            $table->addCell(2000)->addText('Age Category', ['bold' => true]);
            $table->addCell(2000)->addText('Weight Category', ['bold' => true]);
            $table->addCell(2000)->addText('Club', ['bold' => true]);

            // Add an empty row for user to fill
            $table->addRow();
            $table->addCell(500)->addText('1');
            $table->addCell(3000)->addText('');
            $table->addCell(1500)->addText('');
            $table->addCell(2000)->addText('');
            $table->addCell(2000)->addText('');
            $table->addCell(2000)->addText('');

            $tempFile = tempnam(sys_get_temp_dir(), 'registration_template_docx');
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($tempFile);

            return response()->download($tempFile, 'registration_template.docx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'registration_template_csv');
        $handle = fopen($tempFile, 'w');
        // Header row
        fputcsv($handle, ['No', 'Full Name', 'Gender', 'Age Category', 'Weight Category', 'Club']);
        // Example row
        fputcsv($handle, ['1', 'John Doe', 'Male', 'Senior', '-66kg', 'Kurash Club']);
        fclose($handle);

        return response()->download($tempFile, 'registration_template.csv', [
            'Content-Type' => 'text/csv',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Display the specified tournament.
     *
     * @param  \App\Models\Tournament  $tournament
     * @return \Inertia\Response
     */
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

    /**
     * Show the form for editing the specified tournament.
     *
     * @param  \App\Models\Tournament  $tournament
     * @return \Inertia\Response
     */
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

    /**
     * Update the specified tournament in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Tournament  $tournament
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Tournament $tournament)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'tournament_date' => 'required|date',
            'status' => 'required|in:draft,open,ongoing,completed',
            'registrations' => 'array',
            'registrations.*.player_id' => 'required|exists:players,id',
            'registrations.*.tournament_weight_category_id' => 'required|exists:weight_categories,id',
        ]);

        DB::transaction(function () use ($validated, $tournament) {
            $tournament->update([
                'name' => $validated['name'],
                'location' => $validated['location'] ?? null,
                'tournament_date' => $validated['tournament_date'],
                'status' => $validated['status'],
            ]);

            $registrations = collect($validated['registrations'] ?? [])
                ->map(fn($registration) => [
                    'player_id' => (int) $registration['player_id'],
                    'tournament_weight_category_id' => (int) $registration['tournament_weight_category_id'],
                ])
                ->unique(fn($registration) => $registration['player_id'] . '-' . $registration['tournament_weight_category_id'])
                ->values();

            $playerIds = $registrations->pluck('player_id')->unique();
            $playersMap = Player::whereIn('id', $playerIds)->get(['id', 'gender', 'full_name'])->keyBy('id');
            $invalidPlayers = $playersMap
                ->where(function ($query) {
                    $query->where('status', 'inactive')
                        ->orWhere(function ($q) {
                            $q->whereNotNull('membership_expires_at')
                                ->where('membership_expires_at', '<', now());
                        });
                })
                ->pluck('full_name');

            if ($invalidPlayers->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'registrations' => 'The following players have expired memberships or are inactive: ' . $invalidPlayers->implode(', '),
                ]);
            }

            $categoryMap = WeightCategory::query()
                ->whereIn('id', $registrations->pluck('tournament_weight_category_id'))
                ->get(['id', 'age_category_id', 'gender'])
                ->keyBy('id');

            $genderMismatches = collect($registrations)->filter(function ($registration) use ($playersMap, $categoryMap) {
                $player = $playersMap->get($registration['player_id']);
                $category = $categoryMap->get($registration['tournament_weight_category_id']);
                if (!$player || !$category) return false;
                $pg = strtolower($player->gender);
                $cg = strtolower($category->gender);
                return (($cg === 'male' || $cg === 'm') && !($pg === 'male' || $pg === 'm')) ||
                    (($cg === 'female' || $cg === 'f') && !($pg === 'female' || $pg === 'f'));
            })->map(fn($reg) => $playersMap->get($reg['player_id'])?->full_name)->filter()->values();

            if ($genderMismatches->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'registrations' => 'The following players do not match the selected category gender: ' . $genderMismatches->implode(', '),
                ]);
            }

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

    /**
     * Store a newly created weight category.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Remove the specified weight category from storage.
     *
     * @param  \App\Models\WeightCategory  $weightCategory
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Remove the specified tournament from storage.
     *
     * @param  \App\Models\Tournament  $tournament
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Tournament $tournament)
    {
        $tournament->delete();

        return redirect()
            ->route('admin.tournaments.index')
            ->with('success', 'Tournament deleted successfully.');
    }

    /**
     * Map players to a standardized format for the frontend.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function mapPlayers()
    {
        $today = now()->timezone('Asia/Manila')->startOfDay();
        $nextMonth = $today->copy()->addDays(30);

        return Player::query()
            ->select('id', 'full_name', 'gender', 'club', 'status', 'membership_expires_at', 'birthday as dob')
            ->orderBy('full_name')
            ->get()
            ->map(function ($player) use ($today, $nextMonth) {
                // Calculate dynamic status to match Dashboard logic
                $status = $player->status;
                $expires = $player->membership_expires_at;

                if ($status === 'inactive') {
                    // Already inactive, keep it
                } elseif ($expires) {
                    $expiresDate = $expires->timezone('Asia/Manila')->format('Y-m-d');
                    $todayDate = $today->format('Y-m-d');
                    $nextMonthDate = $nextMonth->format('Y-m-d');

                    if ($expiresDate <= $todayDate) {
                        $status = 'inactive';
                    } elseif ($expiresDate <= $nextMonthDate) {
                        $status = 'expiring';
                    }
                }

                $player->status = $status;
                return $player;
            });
    }

    /**
     * Map tournament weight categories to a standardized format for the frontend.
     *
     * @return \Illuminate\Support\Collection
     */
    private function mapTournamentWeightCategories()
    {
        return WeightCategory::query()
            ->with('ageCategory:id,name')
            ->orderBy('gender')
            ->orderBy('age_category_id')
            ->orderBy('min_weight')
            ->get()
            ->map(fn($category) => $this->formatWeightCategory($category))
            ->values();
    }

    /**
     * Format a weight category model to an array.
     *
     * @param  \App\Models\WeightCategory  $category
     * @return array
     */
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

    /**
     * Normalize gender string to a standard format.
     *
     * @param  string  $value
     * @return string|null
     */
    private function normalizeGender(string $value): ?string
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'male', 'm' => 'Male',
            'female', 'f' => 'Female',
            default => null,
        };
    }

    /**
     * Parse a weight category name into a min/max range.
     *
     * @param  string  $name
     * @return array
     */
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
