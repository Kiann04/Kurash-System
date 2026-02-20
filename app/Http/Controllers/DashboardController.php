<?php

namespace App\Http\Controllers;

use App\Models\Player;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard', [
            'totalPlayers' => Player::count(),
            'activePlayers' => Player::where('status', 'active')->count(),
            'inactivePlayers' => Player::where('status', 'inactive')->count(),
            'recentPlayers' => Player::latest('created_at')->take(5)->get()->map(function ($player) {
                return [
                    'id' => $player->id,
                    'name' => $player->full_name,
                    'gender' => $player->gender ?? 'N/A',
                    'age' => $player->age,
                    'club' => $player->club ?? '-',
                    'expiry_date' => $player->membership_expires_at ? $player->membership_expires_at->format('M d, Y') : 'N/A',
                    'status' => $player->status,
                ];
            }),
        ]);
    }
}
