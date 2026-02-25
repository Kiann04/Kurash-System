<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Player;
use Inertia\Inertia;
use Inertia\Response;

class AthleteController extends Controller
{
    public function index(): Response
    {
        $players = Player::query()
            ->select(['id', 'full_name', 'gender', 'club', 'profile_image'])
            ->orderBy('full_name')
            ->paginate(20);

        return Inertia::render('public/Athletes', [
            'players' => $players,
        ]);
    }
}
