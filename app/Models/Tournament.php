<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Tournament Model
 * 
 * Represents a competition event.
 * Acts as the root entity for registrations, brackets, and matches.
 */
class Tournament extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'tournament_date',
        'status', // e.g., 'upcoming', 'ongoing', 'completed'
    ];

    protected $casts = [
        'tournament_date' => 'date',
    ];

    /**
     * Get the registrations for the tournament.
     * Each registration links a player to a specific weight category in this tournament.
     */
    public function registrations()
    {
        return $this->hasMany(TournamentRegistration::class);
    }

    /**
     * Get the brackets generated for this tournament.
     * Each bracket corresponds to a specific gender, age, and weight category.
     */
    public function brackets()
    {
        return $this->hasMany(Bracket::class);
    }
}
