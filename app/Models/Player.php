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
        'gender',
        'club',
        'address',
        'email',
        'emergency_contact',
        'emergency_contact_number',
        'registered_at',
        'membership_expires_at',
        'status',
    ];

    protected $casts = [
        'birthday' => 'date',
        'registered_at' => 'date',
        'membership_expires_at' => 'date',
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
