<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Bracket Model
 * 
 * Represents a specific competition division (e.g., Male - Senior - -66kg).
 * Contains the matches and results for that division.
 */
class Bracket extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'gender',
        'age_category_id',
        'weight_category_id',
        'format', // 'single_elimination', 'round_robin'
        'rounds', // JSON field storing structure if needed, or derived from matches
        'pools',
    ];

    /**
     * The tournament this bracket belongs to.
     */
    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * The matches scheduled for this bracket.
     */
    public function matches()
    {
        return $this->hasMany(TournamentMatch::class);
    }

    /**
     * The age category associated with this bracket.
     */
    public function ageCategory()
    {
        return $this->belongsTo(AgeCategory::class);
    }

    /**
     * The weight category associated with this bracket.
     */
    public function weightCategory()
    {
        return $this->belongsTo(WeightCategory::class);
    }
}
