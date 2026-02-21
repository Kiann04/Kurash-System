<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'tournament_date',
        'status',
    ];

    protected $casts = [
        'tournament_date' => 'date',
    ];

    public function registrations()
    {
        return $this->hasMany(TournamentRegistration::class);
    }

    public function brackets()
    {
        return $this->hasMany(Bracket::class);
    }
}
