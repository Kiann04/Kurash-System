<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'birthday',
        'club',
        'address',
        'email',
        'emergency_contact',
        'emergency_contact_number',
        'membership_start_date',
        'membership_end_date',
        'is_active',
    ];

    protected $casts = [
        'birthday' => 'date',
        'membership_start_date' => 'date',
        'membership_end_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Auto compute age
    public function getAgeAttribute()
    {
        return Carbon::parse($this->birthday)->age;
    }

    // Relationships
    public function tournamentRegistrations()
    {
        return $this->hasMany(TournamentRegistration::class);
    }

    public function matchesAsPlayerOne()
    {
        return $this->hasMany(TournamentMatch::class, 'player_one_id');
    }

    public function matchesAsPlayerTwo()
    {
        return $this->hasMany(TournamentMatch::class, 'player_two_id');
    }
}