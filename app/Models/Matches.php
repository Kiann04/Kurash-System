<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Matches extends Model
{
    use HasFactory;

    protected $fillable = [
        'bracket_id',
        'player_one_id',
        'player_two_id',
        'scheduled_at',
        'winner_id',
        'status',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function bracket()
    {
        return $this->belongsTo(Bracket::class);
    }

    public function playerOne()
    {
        return $this->belongsTo(Player::class, 'player_one_id');
    }

    public function playerTwo()
    {
        return $this->belongsTo(Player::class, 'player_two_id');
    }

    public function winner()
    {
        return $this->belongsTo(Player::class, 'winner_id');
    }

    public function result()
    {
        return $this->hasOne(MatchResult::class);
    }
}