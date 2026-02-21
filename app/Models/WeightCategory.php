<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeightCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'min_weight',
        'max_weight',
        'age_category_id',
    ];

    public function registrations()
    {
        return $this->hasMany(TournamentRegistration::class);
    }
    public function ageCategory()
    {
        return $this->belongsTo(AgeCategory::class);
    }
}