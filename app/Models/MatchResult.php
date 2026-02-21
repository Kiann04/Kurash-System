<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatchResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'player_one_score',
        'player_two_score',
        'method',
    ];

    public function match()
    {
        return $this->belongsTo(TournamentMatch::class, 'match_id');
    }
}
