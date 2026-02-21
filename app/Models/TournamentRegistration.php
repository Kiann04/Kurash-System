<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TournamentRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'player_id',
        'age_category_id',
        'weight_category_id',
        'weigh_in_weight',
        'status',

    ];

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function ageCategory()
    {
        return $this->belongsTo(AgeCategory::class);
    }

    public function weightCategory()
    {
        return $this->belongsTo(WeightCategory::class);
    }
}