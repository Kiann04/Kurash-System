<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Player;
use Inertia\Inertia;
use Inertia\Response;

class RankingController extends Controller
{
    public function index(): Response
    {
        $players = Player::query()
            ->select(['id', 'full_name', 'gender', 'club', 'profile_image'])
            ->orderBy('full_name')
            ->get()
            ->map(function ($player) {
                return [
                    'id' => $player->id,
                    'name' => $player->full_name,
                    'gender' => $player->gender,
                    'club' => $player->club ?? 'Independent',
                    'profile_image' => $player->profile_image,
                    'points' => 0, // Placeholder for now
                ];
            });

        return Inertia::render('public/Rankings', [
            'players' => $players,
        ]);
    }
}
