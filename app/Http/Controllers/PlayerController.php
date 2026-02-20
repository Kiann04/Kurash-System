<?php

namespace App\Http\Controllers;

use App\Models\Player;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlayerController extends Controller
{
    public function index(Request $request)
    {
        $players = Player::query()
            ->when($request->search, function ($query, $search) {
                $query->where('full_name', 'like', "%{$search}%")
                      ->orWhere('club', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%");
            })
            ->when($request->gender, function ($query, $gender) {
                if ($gender !== 'all') {
                    $query->where('gender', $gender);
                }
            })
            ->when($request->status, function ($query, $status) {
                if ($status !== 'all') {
                    $query->where('status', $status);
                }
            })
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($player) => [
                'id' => $player->id,
                'full_name' => $player->full_name,
                'gender' => $player->gender ?? 'N/A',
                'birthday' => $player->birthday->format('Y-m-d'),
                'age' => $player->age,
                'club' => $player->club ?? '-',
                'address' => $player->address ?? '-',
                'membership_expires_at' => $player->membership_expires_at ? $player->membership_expires_at->format('M d, Y') : 'N/A',
                'status' => $player->status,
            ]);

        return Inertia::render('admin/players/Index', [
            'players' => $players,
            'filters' => $request->only(['search', 'gender', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/players/CreatePlayer');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'gender' => 'required|in:Male,Female',
            'birthday' => 'required|date',
            'club' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'email' => 'required|email|unique:players,email',
            'emergency_contact' => 'required|string|max:255',
            'emergency_contact_number' => 'required|string|max:20',
            'registered_at' => 'required|date',
        ]);

        // Auto-calculate membership expiry (1 year from registration)
        $validated['membership_expires_at'] = \Carbon\Carbon::parse($validated['registered_at'])->addYear();
        $validated['status'] = 'active';

        Player::create($validated);

        return redirect()->route('admin.players.index')->with('success', 'Player created successfully.');
    }

    public function edit(Player $player)
    {
        return Inertia::render('admin/players/EditPlayer', [
            'player' => $player,
        ]);
    }

    public function show(Player $player)
    {
        return Inertia::render('admin/players/ShowPlayer', [
            'player' => [
                'id' => $player->id,
                'full_name' => $player->full_name,
                'gender' => $player->gender ?? 'N/A',
                'age' => $player->age,
                'club' => $player->club ?? '-',
                'address' => $player->address ?? '-',
                'email' => $player->email,
                'emergency_contact' => $player->emergency_contact,
                'emergency_contact_number' => $player->emergency_contact_number,
                'registered_at' => $player->registered_at->format('Y-m-d'),
                'membership_expires_at' => $player->membership_expires_at ? $player->membership_expires_at->format('M d, Y') : 'N/A',
                'status' => $player->status,
            ],
        ]);
    }

    public function update(Request $request, Player $player)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'gender' => 'required|in:Male,Female',
            'birthday' => 'required|date',
            'club' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'email' => 'required|email|unique:players,email,' . $player->id,
            'emergency_contact' => 'required|string|max:255',
            'emergency_contact_number' => 'required|string|max:20',
            'registered_at' => 'required|date',
        ]);

        // Update membership expiry if registered_at changes
        if ($request->has('registered_at') && $request->registered_at !== $player->registered_at->format('Y-m-d')) {
            $validated['membership_expires_at'] = \Carbon\Carbon::parse($validated['registered_at'])->addYear();
        }

        $player->update($validated);

        return redirect()->route('admin.players.index')->with('success', 'Player updated successfully.');
    }
}
