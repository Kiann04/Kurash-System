<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Player;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // Use a fixed date reference to match PlayerController logic
        $today = now()->timezone('Asia/Manila')->startOfDay();
        $nextMonth = $today->copy()->addDays(30);

        $activeCount = Player::where(function ($q) use ($nextMonth) {
            $q->whereDate('membership_expires_at', '>', $nextMonth)
              ->orWhereNull('membership_expires_at');
        })->where('status', 'active')->count();

        $expiringCount = Player::where(function ($q) use ($today, $nextMonth) {
            $q->whereDate('membership_expires_at', '>', $today)
              ->whereDate('membership_expires_at', '<=', $nextMonth);
        })->where('status', 'active')->count();

        $inactiveCount = Player::where(function ($q) use ($today) {
            $q->whereDate('membership_expires_at', '<=', $today)
              ->orWhere('status', 'inactive');
        })->count();

        return Inertia::render('Dashboard', [
            'totalPlayers' => Player::count(),
            'activePlayers' => $activeCount,
            'expiringPlayers' => $expiringCount,
            'inactivePlayers' => $inactiveCount,
            'recentPlayers' => Player::latest('created_at')->take(5)->get()->map(function ($player) use ($today, $nextMonth) {
                // Calculate dynamic status
                $status = 'active';
                $expires = $player->membership_expires_at;
                
                if ($player->status === 'inactive') {
                    $status = 'inactive';
                } elseif ($expires) {
                    $expiresDate = $expires->timezone('Asia/Manila')->format('Y-m-d');
                    $todayDate = $today->format('Y-m-d');
                    $nextMonthDate = $nextMonth->format('Y-m-d');

                    if ($expiresDate <= $todayDate) {
                        $status = 'inactive';
                    } elseif ($expiresDate <= $nextMonthDate) {
                        $status = 'expiring';
                    }
                }

                return [
                    'id' => $player->id,
                    'name' => $player->full_name,
                    'gender' => $player->gender ?? 'N/A',
                    'age' => $player->age,
                    'club_location' => trim(($player->club ?? '-') . ($player->address ? ' • ' . $player->address : '')),
                    'membership_start' => $player->membership_start_date ? $player->membership_start_date->format('M d, Y') : 'N/A',
                    'membership_end' => $player->membership_expires_at ? $player->membership_expires_at->format('M d, Y') : 'N/A',
                    'status' => $status,
                ];
            }),
        ]);
    }
}
