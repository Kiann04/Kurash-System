<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Carbon\Carbon;

class Player extends Model
{
    protected $fillable = [
        'full_name',
        'birthday',
        'club',
        'address',
        'email',
        'emergency_contact',
        'emergency_contact_number',
        'registered_at',
        'membership_expires_at',
        'status',
    ];

    protected $casts = [
        'birthday' => 'date',
        'registered_at' => 'date',
        'membership_expires_at' => 'date',
    ];

    // Automatically set 1-year expiration
    protected static function booted()
    {
        static::creating(function ($player) {

            $player->registered_at = now();
            $player->membership_expires_at = now()->addYear();
            $player->status = 'active';
        });
    }

    public function checkMembershipStatus()
    {
        if (now()->greaterThan($this->membership_expires_at)) {
            $this->update(['status' => 'inactive']);
        }
    }
}