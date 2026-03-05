<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * TournamentRegistration Model
 * 
 * Represents a player's entry into a specific tournament.
 * Links the player to their chosen age and weight category for the event.
 */
class TournamentRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'player_id',
        'age_category_id',
        'weight_category_id',
        'weigh_in_weight',
        'status', // 'pending', 'approved', 'rejected'

    ];

    /**
     * The tournament the player is registering for.
     */
    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * The player who is registering.
     */
    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * The age category selected for this registration.
     */
    public function ageCategory()
    {
        return $this->belongsTo(AgeCategory::class);
    }

    /**
     * The weight category selected for this registration.
     */
    public function weightCategory()
    {
        return $this->belongsTo(WeightCategory::class);
    }
}