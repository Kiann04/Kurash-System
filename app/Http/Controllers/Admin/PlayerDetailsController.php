<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Player;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlayerDetailsController extends Controller
{
    /**
     * Display a listing of players with detailed contact information.
     *
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $query = Player::query();

        // Apply filters
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('full_name', 'like', "%{$request->search}%")
                  ->orWhere('club', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->gender && $request->gender !== 'all') {
            $query->where('gender', $request->gender);
        }

        // Get players with specific columns needed for the details view
        $players = $query->orderBy('full_name')
            ->select([
                'id',
                'full_name',
                'address',
                'email',
                'emergency_contact',
                'emergency_contact_number',
                'birthday',
                'gender',
                'club',
                'profile_image'
            ])
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/player-details/Index', [
            'players' => $players,
            'filters' => [
                'search' => $request->search,
                'gender' => $request->gender,
            ],
        ]);
    }
}
