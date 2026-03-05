<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * TournamentMatch Model
 * 
 * Represents a single bout between two players in a bracket.
 * Stores the schedule, participants, and result of the match.
 */
class TournamentMatch extends Model
{
    use HasFactory;

    protected $table = 'matches'; // IMPORTANT: Custom table name override

    protected $fillable = [
        'bracket_id',
        'round_number',
        'match_number',
        'player_one_id', // Nullable for byes or TBD
        'player_two_id', // Nullable for byes or TBD
        'match_date',
        'winner_id',
        'status', // 'scheduled', 'completed', 'ongoing'
        'global_match_order',
        'is_bronze',
    ];

    protected $casts = [
        'match_date' => 'datetime',
        'is_bronze' => 'boolean',
    ];

    /**
     * The bracket this match belongs to.
     */
    public function bracket()
    {
        return $this->belongsTo(Bracket::class);
    }

    /**
     * The first player (Blue/Upper).
     */
    public function playerOne()
    {
        return $this->belongsTo(Player::class, 'player_one_id');
    }

    /**
     * The second player (Green/Lower).
     */
    public function playerTwo()
    {
        return $this->belongsTo(Player::class, 'player_two_id');
    }

    /**
     * The winner of the match.
     */
    public function winner()
    {
        return $this->belongsTo(Player::class, 'winner_id');
    }

    /**
     * The detailed result/score of the match (if applicable).
     */
    public function result()
    {
        return $this->hasOne(MatchResult::class, 'match_id');
    }
}
