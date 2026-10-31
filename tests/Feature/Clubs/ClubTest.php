<?php

use App\Models\Club;
use App\Models\Event;
use App\Models\SchoolClass;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $class = SchoolClass::factory()->named(10, 'Б')->create();
    $this->student = User::factory()->inClass($class)->create();
    $this->leader = User::factory()->inClass($class)->create();
    $this->teacher = User::factory()->teacher()->create();
    $this->admin = User::factory()->admin()->create();
    $this->club = Club::factory()->ledBy($this->leader)->create(['name' => 'Дебатен клуб']);
});

test('everyone including teachers can browse clubs; inactive clubs are hidden from non-admins', function () {
    $inactive = Club::factory()->inactive()->create();

    foreach ([$this->student, $this->teacher] as $user) {
        $this->actingAs($user)->get('/clubs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Clubs/Index')->has('clubs', 1));
        $this->actingAs($user)->get(route('clubs.show', $this->club))->assertOk();
        $this->actingAs($user)->get(route('clubs.show', $inactive))->assertForbidden();
    }

    $this->actingAs($this->admin)->get('/clubs')->assertInertia(fn (Assert $page) => $page->has('clubs', 2));
});

test('only admins create, edit and delete clubs; slug is generated from the name', function () {
    $data = ['name' => 'Клуб по роботика', 'description' => 'Строим роботи.', 'leader_id' => $this->student->id, 'is_active' => true];

    foreach ([$this->student, $this->leader, $this->teacher] as $user) {
        $this->actingAs($user)->get(route('clubs.create'))->assertForbidden();
        $this->actingAs($user)->post(route('clubs.store'), $data)->assertForbidden();
        $this->actingAs($user)->patch(route('clubs.update', $this->club), $data)->assertForbidden();
        $this->actingAs($user)->delete(route('clubs.destroy', $this->club))->assertForbidden();
    }

    $this->actingAs($this->admin)->post(route('clubs.store'), $data)->assertRedirect(route('clubs.show', 'klub-po-robotika'));
    $this->actingAs($this->admin)->post(route('clubs.store'), $data)->assertRedirect(route('clubs.show', 'klub-po-robotika-2'));

    $this->actingAs($this->admin)->post(route('clubs.store'), [...$data, 'leader_id' => $this->teacher->id])
        ->assertSessionHasErrors('leader_id');

    $this->actingAs($this->admin)->delete(route('clubs.destroy', $this->club))->assertRedirect();
    expect(Club::count())->toBe(2);
});

test('students join and leave clubs; teachers cannot join', function () {
    $this->actingAs($this->student)->post(route('clubs.join', $this->club))->assertRedirect();
    $this->actingAs($this->student)->post(route('clubs.join', $this->club));
    expect($this->club->members()->count())->toBe(1)->and($this->club->hasMember($this->student))->toBeTrue();

    $this->actingAs($this->student)->get(route('clubs.show', $this->club))
        ->assertInertia(fn (Assert $page) => $page->where('club.is_member', true)->where('club.members_count', 1));

    $this->actingAs($this->student)->delete(route('clubs.leave', $this->club))->assertRedirect();
    expect($this->club->members()->count())->toBe(0);

    $this->actingAs($this->teacher)->post(route('clubs.join', $this->club))->assertForbidden();
    $this->actingAs($this->student)->post(route('clubs.join', Club::factory()->inactive()->create()))->assertForbidden();
    expect($this->club->members()->count())->toBe(0);
});

test('a club leader manages events of their own club only', function () {
    $otherClub = Club::factory()->ledBy($this->student)->create();
    $otherEvent = Event::factory()->create(['club_id' => $otherClub->id]);
    $ownEvent = Event::factory()->create(['club_id' => $this->club->id]);
    $event = ['title' => 'Дебат за изкуствения интелект', 'starts_at' => now()->addWeek()->format('Y-m-d\TH:i'), 'location' => 'Актова зала'];

    $this->actingAs($this->leader)->post(route('events.store'), [...$event, 'club_id' => $this->club->id])->assertRedirect();
    $this->actingAs($this->leader)->post(route('events.store'), [...$event, 'club_id' => $otherClub->id])->assertSessionHasErrors('club_id');
    $this->actingAs($this->leader)->post(route('events.store'), [...$event, 'club_id' => null])->assertSessionHasErrors('club_id');

    $this->actingAs($this->leader)->patch(route('events.update', $ownEvent), [...$event, 'club_id' => $this->club->id])->assertRedirect();
    $this->actingAs($this->leader)->get(route('events.edit', $otherEvent))->assertForbidden();
    $this->actingAs($this->leader)->patch(route('events.update', $otherEvent), [...$event, 'club_id' => $this->club->id])->assertForbidden();
    $this->actingAs($this->leader)->delete(route('events.destroy', $otherEvent))->assertForbidden();
    $this->actingAs($this->leader)->delete(route('events.destroy', $ownEvent))->assertRedirect();

    expect(Event::where('club_id', $this->club->id)->count())->toBe(1)
        ->and($ownEvent->fresh())->toBeNull()
        ->and($otherEvent->fresh()->title)->not->toBe($event['title']);
});

test('only admins create school-wide events; plain students and teachers cannot create events', function () {
    $event = ['title' => 'Ден на испанския език', 'starts_at' => now()->addWeek()->format('Y-m-d\TH:i'), 'location' => 'Двор', 'club_id' => null];

    $this->actingAs($this->student)->get(route('events.create'))->assertForbidden();
    $this->actingAs($this->teacher)->post(route('events.store'), $event)->assertForbidden();
    $this->actingAs($this->admin)->post(route('events.store'), $event)->assertRedirect();
    $this->actingAs($this->admin)->post(route('events.store'), [...$event, 'ends_at' => now()->format('Y-m-d\TH:i')])
        ->assertSessionHasErrors('ends_at');

    expect(Event::whereNull('club_id')->count())->toBe(1)
        ->and(Event::first()->created_by)->toBe($this->admin->id);
});

test('RSVP: going/interested can be set, changed and cancelled; teachers cannot RSVP', function () {
    $event = Event::factory()->create(['club_id' => $this->club->id]);

    $this->actingAs($this->student)->post(route('events.rsvp', $event), ['status' => 'interested'])->assertRedirect();
    $this->actingAs($this->leader)->post(route('events.rsvp', $event), ['status' => 'going']);
    $this->actingAs($this->student)->post(route('events.rsvp', $event), ['status' => 'going']);

    $this->actingAs($this->student)->get(route('events.show', $event))
        ->assertInertia(fn (Assert $page) => $page
            ->where('event.going_count', 2)
            ->where('event.interested_count', 0)
            ->where('event.my_rsvp', 'going'));

    $this->actingAs($this->student)->post(route('events.rsvp', $event), ['status' => 'maybe'])->assertSessionHasErrors('status');
    $this->actingAs($this->student)->delete(route('events.rsvp.cancel', $event))->assertRedirect();
    expect($event->attendees()->count())->toBe(1);

    $this->actingAs($this->teacher)->get(route('events.show', $event))->assertOk();
    $this->actingAs($this->teacher)->post(route('events.rsvp', $event), ['status' => 'going'])->assertForbidden();
});

test('events list shows upcoming events first and past events on request', function () {
    $soon = Event::factory()->create(['club_id' => $this->club->id, 'starts_at' => now()->addDay()]);
    $later = Event::factory()->schoolWide()->create(['starts_at' => now()->addWeeks(2)]);
    $past = Event::factory()->past()->create(['club_id' => $this->club->id]);
    Event::factory()->create(['club_id' => Club::factory()->inactive()->create()->id]);

    $this->actingAs($this->teacher)->get('/events')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Events/Index')
            ->has('events.data', 2)
            ->where('events.data.0.id', $soon->id)
            ->where('events.data.1.id', $later->id));

    $this->actingAs($this->student)->get('/events?past=1')
        ->assertInertia(fn (Assert $page) => $page->has('events.data', 1)->where('events.data.0.id', $past->id));

    $this->actingAs($this->student)->get('/events?club=school')
        ->assertInertia(fn (Assert $page) => $page->has('events.data', 1)->where('events.data.0.id', $later->id));
});
