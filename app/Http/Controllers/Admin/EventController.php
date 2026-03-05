<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::query()
            ->latest('start_date')
            ->paginate(10);

        return Inertia::render('admin/events/Index', [
            'events' => $events,
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/events/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'location' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:draft,published',
            'image' => 'nullable|image|max:2048',
        ]);

        unset($validated['image']);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $storedPath = $request->file('image')->store('events', 'public');
            $imagePath = Storage::url($storedPath);
        }

        Event::create([
            ...$validated,
            'image_path' => $imagePath,
        ]);

        return redirect()
            ->route('admin.events.index')
            ->with('success', 'Event created successfully.');
    }

    public function show(Event $event)
    {
        return Inertia::render('admin/events/Show', [
            'event' => $event,
        ]);
    }

    public function edit(Event $event)
    {
        return Inertia::render('admin/events/Edit', [
            'event' => $event,
        ]);
    }

    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'location' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:draft,published',
            'image' => 'nullable|image|max:2048',
        ]);

        unset($validated['image']);

        $imagePath = $event->image_path;
        if ($request->hasFile('image')) {
            if ($imagePath && Str::startsWith($imagePath, '/storage/')) {
                $relativePath = ltrim(Str::after($imagePath, '/storage/'), '/');
                Storage::disk('public')->delete($relativePath);
            }

            $storedPath = $request->file('image')->store('events', 'public');
            $imagePath = Storage::url($storedPath);
        }

        $event->update([
            ...$validated,
            'image_path' => $imagePath,
        ]);

        return redirect()
            ->route('admin.events.index')
            ->with('success', 'Event updated successfully.');
    }

    public function destroy(Event $event)
    {
        if ($event->image_path && Str::startsWith($event->image_path, '/storage/')) {
            $relativePath = ltrim(Str::after($event->image_path, '/storage/'), '/');
            Storage::disk('public')->delete($relativePath);
        }

        $event->delete();

        return redirect()
            ->route('admin.events.index')
            ->with('success', 'Event deleted successfully.');
    }
}
