<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
                // Use a fixed date reference to avoid mutation issues within closures
                $today = now()->timezone('Asia/Manila')->startOfDay();
                $nextMonth = $today->copy()->addDays(30);

                if ($status === 'active') {
                    $query->where(function ($q) use ($nextMonth) {
                        $q->where(function ($sub) use ($nextMonth) {
                            $sub->whereDate('membership_expires_at', '>', $nextMonth)
                                ->orWhereNull('membership_expires_at');
                        })->where('status', 'active');
                    });
                }

                if ($status === 'inactive') {
                    $query->where(function ($q) use ($today) {
                        $q->whereDate('membership_expires_at', '<=', $today)
                            ->orWhere('status', 'inactive');
                    });
                }

                if ($status === 'expiring_soon') {
                    $query->where(function ($q) use ($today, $nextMonth) {
                        $q->whereDate('membership_expires_at', '>', $today)
                            ->whereDate('membership_expires_at', '<=', $nextMonth)
                            ->where('status', 'active');
                    });
                }
            })
            ->when($request->membership_start, function ($query, $date) {
                $query->whereDate('membership_start_date', '>=', $date);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn($player) => [
                'id' => $player->id,
                'full_name' => $player->full_name,
                'gender' => $player->gender ?? 'N/A',
                'birthday' => $player->birthday->format('Y-m-d'),
                'age' => $player->age,
                'club' => $player->club ?? '-',
                'address' => $player->address ?? '-',
                'membership_start_date' => $player->membership_start_date ? $player->membership_start_date->format('M d, Y') : 'N/A',
                'membership_expires_at' => $player->membership_expires_at ? $player->membership_expires_at->format('M d, Y') : 'N/A',
                'status' => $player->status,
            ]);

        return Inertia::render('admin/players/Index', [
            'players' => $players,
            'filters' => $request->only(['search', 'gender', 'status', 'membership_start']),
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
            'membership_start_date' => 'nullable|date',
            'membership_expires_at' => 'nullable|date',
        ]);

        // Set membership start date from input or fallback to registration
        $validated['membership_start_date'] = $validated['membership_start_date'] ?? $validated['registered_at'];

        // Set expiry from input or auto-calculate (+1 year from start)
        $validated['membership_expires_at'] = $validated['membership_expires_at'] ?? \Carbon\Carbon::parse($validated['membership_start_date'])->addYear();

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
            'membership_start_date' => 'nullable|date',
            'membership_expires_at' => 'nullable|date',
        ]);

        // Set membership start date from input or fallback to registration
        $validated['membership_start_date'] = $validated['membership_start_date'] ?? $validated['registered_at'];

        // Set expiry from input or auto-calculate (+1 year from start)
        $validated['membership_expires_at'] = $validated['membership_expires_at'] ?? \Carbon\Carbon::parse($validated['membership_start_date'])->addYear();

        $player->update($validated);

        return redirect()->route('admin.players.index')->with('success', 'Player updated successfully.');
    }
    public function renew(Player $player)
    {
        $player->update([
            'registered_at' => now(),
            'membership_expires_at' => now()->addYear(),
            'status' => 'active',
        ]);

        return back()->with('success', 'Membership renewed successfully.');
    }
}
