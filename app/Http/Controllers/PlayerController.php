<?php
namespace App\Http\Controllers; 
use Inertia\Inertia;
use App\Models\Player;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
    public function index() {
        $players = Player::latest()->get();
        return Inertia::render('admin/players/Index', compact('players'));
    }

    public function create() {
        return Inertia::render('admin/players/CreatePlayer');
    }

    public function store(Request $request) {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'gender' => 'required|in:Male,Female',
            'age' => 'required|integer|min:4',
            'weight' => 'required|numeric|min:10',
        ]);

        Player::create($request->all());

        return redirect()->route('admin.players.index')->with('success', 'Player created successfully.');
    }
}