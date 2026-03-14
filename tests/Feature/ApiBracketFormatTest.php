<?php

use App\Models\Tournament;
use App\Models\Bracket;
use App\Models\TournamentMatch;
use App\Models\AgeCategory;
use App\Models\WeightCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('includes format fields in full-data and infers single_elimination', function () {
    $tournament = Tournament::query()->create([
        'name' => 'Test Cup',
        'status' => 'ongoing',
        'tournament_date' => now(),
    ]);
    $age = AgeCategory::query()->create(['name' => 'Seniors', 'min_age' => 18, 'max_age' => 60]);
    $weight = WeightCategory::query()->create(['name' => '-60', 'min_weight' => 0, 'max_weight' => 60, 'gender' => 'Male', 'age_category_id' => $age->id]);
    $bracket = Bracket::create([
        'tournament_id' => $tournament->id,
        'gender' => 'male',
        'age_category_id' => $age->id,
        'weight_category_id' => $weight->id,
        'format' => null,
        'rounds' => 2,
    ]);
    TournamentMatch::create([
        'bracket_id' => $bracket->id,
        'round_number' => 1,
        'match_number' => 1,
        'status' => 'scheduled',
    ]);
    TournamentMatch::create([
        'bracket_id' => $bracket->id,
        'round_number' => 1,
        'match_number' => 2,
        'status' => 'scheduled',
    ]);
    TournamentMatch::create([
        'bracket_id' => $bracket->id,
        'round_number' => 2,
        'match_number' => 1,
        'status' => 'scheduled',
    ]);

    $this->withoutMiddleware();
    $resp = $this->getJson("/api/documents/{$tournament->id}/full-data");
    $resp->assertOk();
    $data = $resp->json();
    expect($data)->toHaveKey('brackets');
    $b = collect($data['brackets'])->firstWhere('id', $bracket->id);
    expect($b['format'])->toBe('single_elimination');
    expect($b['rounds'])->toBe(2);
    expect($b['stage_labels'])->toBeArray()->and($b['stage_labels'])->toContain('Semifinals', 'Finals');
});

it('includes format fields in scoreboard-data and infers round_robin', function () {
    $tournament = Tournament::query()->create([
        'name' => 'League',
        'status' => 'ongoing',
        'tournament_date' => now(),
    ]);
    $age = AgeCategory::query()->create(['name' => 'Cadets', 'min_age' => 14, 'max_age' => 15]);
    $weight = WeightCategory::query()->create(['name' => '-44', 'min_weight' => 0, 'max_weight' => 44, 'gender' => 'Female', 'age_category_id' => $age->id]);
    $bracket = Bracket::create([
        'tournament_id' => $tournament->id,
        'gender' => 'female',
        'age_category_id' => $age->id,
        'weight_category_id' => $weight->id,
        'format' => null,
        'rounds' => null,
    ]);
    foreach ([1,2,3] as $round) {
        for ($m = 1; $m <= 2; $m++) {
            TournamentMatch::create([
                'bracket_id' => $bracket->id,
                'round_number' => $round,
                'match_number' => $m,
                'status' => 'scheduled',
            ]);
        }
    }
    $this->withoutMiddleware();
    $resp = $this->getJson("/api/tournaments/{$tournament->id}/scoreboard-data");
    $resp->assertOk();
    $payload = $resp->json();
    $b = collect($payload['brackets'])->firstWhere('id', (string) $bracket->id);
    expect($b['format'])->toBe('round_robin');
    expect($b['rounds'])->toBe(3);
    expect($b['stage_labels'])->toBeNull();
});
