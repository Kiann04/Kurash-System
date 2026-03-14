<?php

use App\Models\Event;
use Inertia\Testing\AssertableInertia as Assert;

test('upcoming events image path uses media route', function () {
    $event = Event::create([
        'title' => 'Test Event',
        'location' => 'Test Location',
        'start_date' => now()->addDays(5)->toDateString(),
        'end_date' => now()->addDays(6)->toDateString(),
        'status' => 'published',
        'image_path' => '/storage/events/example.jpg',
    ]);

    $expectedUrl = route('media', ['path' => 'events/example.jpg']);

    $this->get(route('public.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/Home')
            ->where('events.0.id', $event->id)
            ->where('events.0.image_path', $expectedUrl)
        );
});
