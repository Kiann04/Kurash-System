<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bracket extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'gender',
        'age_category_id',
        'weight_category_id',
        'format',
        'rounds',
    ];

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function matches()
    {
        return $this->hasMany(TournamentMatch::class);
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
