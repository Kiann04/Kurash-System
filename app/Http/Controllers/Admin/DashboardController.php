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
        $yesterday = $today->copy()->subDay();

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
            'recentPlayers' => (function () use ($today, $nextMonth, $yesterday) {
                // Build a unified feed of recent registrations and status changes
                $now = now()->timezone('Asia/Manila');

                // New registrations within the last day
                $recentRegistered = Player::query()
                    ->whereBetween('created_at', [$yesterday, $now])
                    ->get()
                    ->map(function ($p) {
                        return ['player' => $p, 'event_date' => $p->created_at, 'event' => 'registered'];
                    });

                // Recently expired (membership_expires_at crossed today)
                $recentExpired = Player::query()
                    ->whereNotNull('membership_expires_at')
                    ->whereDate('membership_expires_at', '<=', $today)
                    ->whereDate('membership_expires_at', '>=', $yesterday)
                    ->get()
                    ->map(function ($p) {
                        return ['player' => $p, 'event_date' => $p->membership_expires_at, 'event' => 'expired'];
                    });

                // Recently entered expiring window (expires_at ≈ today + 30 days)
                $expiringWindowStart = $yesterday->copy()->addDays(30)->toDateString();
                $expiringWindowEnd = $today->copy()->addDays(30)->toDateString();
                $recentExpiringSoon = Player::query()
                    ->where('status', 'active')
                    ->whereNotNull('membership_expires_at')
                    ->whereDate('membership_expires_at', '>', $today)
                    ->whereDate('membership_expires_at', '<=', $nextMonth)
                    ->whereBetween('membership_expires_at', [$expiringWindowStart, $expiringWindowEnd])
                    ->get()
                    ->map(function ($p) {
                        $eventDate = $p->membership_expires_at?->copy()->subDays(30);
                        return ['player' => $p, 'event_date' => $eventDate, 'event' => 'expiring'];
                    });

                // Manual status changes (activated/inactivated in the last day)
                $recentActivated = Player::query()
                    ->where('status', 'active')
                    ->whereBetween('updated_at', [$yesterday, $now])
                    ->get()
                    ->map(function ($p) {
                        return ['player' => $p, 'event_date' => $p->updated_at, 'event' => 'activated'];
                    });

                $recentInactivated = Player::query()
                    ->where('status', 'inactive')
                    ->whereBetween('updated_at', [$yesterday, $now])
                    ->get()
                    ->map(function ($p) {
                        return ['player' => $p, 'event_date' => $p->updated_at, 'event' => 'inactivated'];
                    });

                // Combine, sort by event_date desc, unique by player id, and take 5
                $combined = collect()
                    ->merge($recentRegistered)
                    ->merge($recentExpired)
                    ->merge($recentExpiringSoon)
                    ->merge($recentActivated)
                    ->merge($recentInactivated)
                    ->sortByDesc(function ($item) {
                        return $item['event_date']?->timestamp ?? 0;
                    })
                    ->unique(function ($item) {
                        return $item['player']->id;
                    })
                    ->values();

                // Fallback: if less than 5, fill with latest registrations
                if ($combined->count() < 5) {
                    $fill = Player::query()
                        ->latest('created_at')
                        ->take(5)
                        ->get()
                        ->map(function ($p) {
                            return ['player' => $p, 'event_date' => $p->created_at, 'event' => 'registered'];
                        });

                    $combined = $fill
                        ->merge($combined)
                        ->sortByDesc(function ($item) {
                            return $item['event_date']?->timestamp ?? 0;
                        })
                        ->unique(function ($item) {
                            return $item['player']->id;
                        })
                        ->values();
                }

                // Limit to 5 and map to frontend shape with dynamic status consistent with counts
                return $combined->take(5)->map(function ($wrapped) use ($today, $nextMonth) {
                    $player = $wrapped['player'];

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
                });
            })(),
        ]);
    }
}
