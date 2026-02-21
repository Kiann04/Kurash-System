<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgeCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'min_age',
        'max_age',
    ];

    public function registrations()
    {
        return $this->hasMany(TournamentRegistration::class);
    }
    public function weightCategories()
    {
        return $this->hasMany(WeightCategory::class);
    }
    
}