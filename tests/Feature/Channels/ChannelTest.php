<?php

use App\Models\Channel;
use App\Models\ChannelMute;
use App\Models\Grade;
use App\Models\Message;
use App\Models\Profile;
use App\Models\Report;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\ChannelService;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->class10b = SchoolClass::factory()->named(10, 'Б')->create(['profile_id' => Profile::create(['name' => 'Испански език'])->id]);
    $this->class11a = SchoolClass::factory()->named(11, 'А')->create();
    $this->student = User::factory()->inClass($this->class10b)->create();
    $this->classmate = User::factory()->inClass($this->class10b)->create();
    $this->other = User::factory()->inClass($this->class11a)->create();
    $this->teacher = User::factory()->teacher()->create();
    $this->moderator = User::factory()->moderator()->inClass($this->class11a)->create();
    $this->admin = User::factory()->admin()->create();

    $this->own = Channel::where('scope_type', 'class')->where('scope_id', $this->class10b->id)->first();
    $this->foreign = Channel::where('scope_type', 'class')->where('scope_id', $this->class11a->id)->first();
});

test('channels are created automatically for every grade, class and profile', function () {
    expect($this->own)->not->toBeNull()
        ->and($this->own->name)->toBe('10Б')
        ->and(Channel::where('scope_type', 'grade')->where('scope_id', $this->class10b->grade_id)->value('name'))->toBe('Випуск 10 клас')
        ->and(Channel::where('scope_type', 'profile')->where('scope_id', $this->class10b->profile_id)->exists())->toBeTrue();

    $profile = Profile::create(['name' => 'Природни науки']);
    expect(Channel::where('scope_type', 'profile')->where('scope_id', $profile->id)->value('name'))->toBe('Профил „Природни науки“');

    $before = Channel::count();
    expect(app(ChannelService::class)->syncAll())->toBe(Grade::count() + SchoolClass::count() + Profile::count())
        ->and(Channel::count())->toBe($before);

    $this->class11a->delete();
    expect($this->foreign->fresh()->is_archived)->toBeTrue();
});

test('teachers get 403 on every channel endpoint and never see channels', function () {
    $message = Message::factory()->create(['channel_id' => $this->own->id, 'user_id' => $this->student->id]);
    $mute = ChannelMute::create(['channel_id' => $this->own->id, 'user_id' => $this->classmate->id, 'muted_until' => now()->addHour()]);

    $requests = [
        ['get', route('channels.index')],
        ['get', route('channels.show', $this->own)],
        ['get', route('channels.messages.index', $this->own)],
        ['getJson', route('channels.messages.index', ['channel' => $this->own, 'after' => 0])],
        ['postJson', route('channels.messages.store', $this->own), ['body' => 'Здравейте']],
        ['deleteJson', route('messages.destroy', $message)],
        ['post', route('channels.mutes.store', $this->own), ['user_id' => $this->student->id, 'minutes' => 15]],
        ['delete', route('mutes.destroy', $mute)],
        ['post', route('reports.store'), ['type' => 'message', 'id' => $message->id, 'reason' => 'Обидно съобщение']],
    ];

    foreach ($requests as $request) {
        [$method, $url] = $request;
        $this->actingAs($this->teacher)->{$method}($url, $request[2] ?? [])->assertForbidden();
    }

    expect(Channel::query()->visibleTo($this->teacher)->count())->toBe(0)
        ->and(Message::count())->toBe(1);

    // Channel data never reaches teachers (Ziggy still lists route names, which hold no data).
    $this->actingAs($this->teacher)->get('/dashboard')->assertOk()->assertDontSee('Випуск 10 клас');
});

test('students only see and use channels of their own class, grade and profile', function () {
    $this->actingAs($this->student)->get(route('channels.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Channels/Show')
            ->has('channels', 3)
            ->where('channels.0.name', '10Б'));

    $this->actingAs($this->student)->get(route('channels.show', $this->foreign))->assertForbidden();
    $this->actingAs($this->student)->getJson(route('channels.messages.index', $this->foreign))->assertForbidden();
    $this->actingAs($this->student)->postJson(route('channels.messages.store', $this->foreign), ['body' => 'Здрасти'])->assertForbidden();

    $foreignMessage = Message::factory()->create(['channel_id' => $this->foreign->id, 'user_id' => $this->other->id]);
    $this->actingAs($this->student)->post(route('reports.store'), ['type' => 'message', 'id' => $foreignMessage->id, 'reason' => 'Обидно съобщение'])
        ->assertForbidden();

    $this->actingAs($this->student)->postJson(route('channels.messages.store', $this->own), ['body' => 'Здравейте, 10Б!'])
        ->assertCreated()
        ->assertJsonPath('message.body', 'Здравейте, 10Б!');

    $this->actingAs($this->admin)->get(route('channels.show', $this->foreign))->assertOk();
    $this->actingAs($this->moderator)->get(route('channels.show', $this->own))->assertOk();
});

test('messages are limited to 2000 characters and rate limited', function () {
    $this->actingAs($this->student)->postJson(route('channels.messages.store', $this->own), ['body' => str_repeat('а', 2001)])
        ->assertUnprocessable()->assertJsonValidationErrors('body');
    $this->actingAs($this->student)->postJson(route('channels.messages.store', $this->own), ['body' => ''])
        ->assertJsonValidationErrors('body');

    foreach (range(1, 18) as $i) {
        $this->actingAs($this->student)->postJson(route('channels.messages.store', $this->own), ['body' => "Съобщение {$i}"])->assertCreated();
    }

    $this->actingAs($this->student)->postJson(route('channels.messages.store', $this->own), ['body' => 'Още едно'])->assertStatus(429);
});

test('polling returns only messages after the given id and deletions since the last poll', function () {
    $first = Message::factory()->create(['channel_id' => $this->own->id, 'user_id' => $this->classmate->id]);
    $since = now()->subSecond()->toIso8601String();
    $second = Message::factory()->create(['channel_id' => $this->own->id, 'user_id' => $this->classmate->id]);
    $first->delete();

    $this->actingAs($this->student)->getJson(route('channels.messages.index', ['channel' => $this->own, 'after' => $first->id, 'since' => $since]))
        ->assertOk()
        ->assertJsonCount(1, 'messages')
        ->assertJsonPath('messages.0.id', $second->id)
        ->assertJsonPath('messages.0.can.delete', false)
        ->assertJsonPath('messages.0.can.report', true)
        ->assertJsonPath('deleted_ids', [$first->id]);
});

test('load more returns older messages in pages', function () {
    Message::factory()->count(35)->create(['channel_id' => $this->own->id, 'user_id' => $this->classmate->id]);
    $ids = Message::orderBy('id')->pluck('id');

    $this->actingAs($this->student)->get(route('channels.show', $this->own))
        ->assertInertia(fn (Assert $page) => $page->has('messages', 30)->where('hasMore', true)->where('messages.0.id', $ids[5]));

    $this->actingAs($this->student)->getJson(route('channels.messages.index', ['channel' => $this->own, 'before' => $ids[5]]))
        ->assertJsonCount(5, 'messages')
        ->assertJsonPath('hasMore', false)
        ->assertJsonPath('messages.0.id', $ids[0]);
});

test('muted users see a notice and cannot post, in the channel or globally', function () {
    $this->actingAs($this->moderator)->post(route('channels.mutes.store', $this->own), [
        'user_id' => $this->student->id, 'minutes' => 60, 'reason' => 'Спам',
    ])->assertSessionHasNoErrors()->assertRedirect();

    $this->actingAs($this->student)->postJson(route('channels.messages.store', $this->own), ['body' => 'Здрасти'])->assertForbidden();
    $this->actingAs($this->student)->get(route('channels.show', $this->own))
        ->assertInertia(fn (Assert $page) => $page->where('mute.reason', 'Спам')->where('channel.can.post', false));

    $grade = Channel::where('scope_type', 'grade')->where('scope_id', $this->class10b->grade_id)->first();
    $this->actingAs($this->student)->postJson(route('channels.messages.store', $grade), ['body' => 'Тук мога'])->assertCreated();

    $this->actingAs($this->admin)->post(route('channels.mutes.store', $this->own), ['user_id' => $this->classmate->id, 'minutes' => 15, 'global' => true]);
    $this->actingAs($this->classmate)->postJson(route('channels.messages.store', $grade), ['body' => 'Здрасти'])->assertForbidden();

    $this->travel(2)->hours();
    $this->actingAs($this->student)->postJson(route('channels.messages.store', $this->own), ['body' => 'Пак съм тук'])->assertCreated();
});

test('only moderators and admins mute; admins cannot be muted; mutes can be lifted', function () {
    $this->actingAs($this->classmate)->post(route('channels.mutes.store', $this->own), ['user_id' => $this->student->id, 'minutes' => 15])->assertForbidden();
    $this->actingAs($this->moderator)->post(route('channels.mutes.store', $this->own), ['user_id' => $this->admin->id, 'minutes' => 15])->assertSessionHasErrors('user_id');
    $this->actingAs($this->moderator)->post(route('channels.mutes.store', $this->own), ['user_id' => $this->student->id, 'minutes' => 7])->assertSessionHasErrors('minutes');

    $this->actingAs($this->moderator)->post(route('channels.mutes.store', $this->own), ['user_id' => $this->student->id, 'minutes' => 15]);
    $mute = ChannelMute::first();
    expect($mute->created_by)->toBe($this->moderator->id);

    $this->actingAs($this->classmate)->delete(route('mutes.destroy', $mute))->assertForbidden();
    $this->actingAs($this->moderator)->delete(route('mutes.destroy', $mute))->assertRedirect();
    $this->actingAs($this->student)->postJson(route('channels.messages.store', $this->own), ['body' => 'Свободен съм'])->assertCreated();
});

test('authors delete their own messages; moderators and admins delete any; others cannot', function () {
    $message = Message::factory()->create(['channel_id' => $this->own->id, 'user_id' => $this->student->id]);
    $another = Message::factory()->create(['channel_id' => $this->own->id, 'user_id' => $this->student->id]);
    $third = Message::factory()->create(['channel_id' => $this->own->id, 'user_id' => $this->student->id]);

    $this->actingAs($this->classmate)->deleteJson(route('messages.destroy', $message))->assertForbidden();
    $this->actingAs($this->other)->deleteJson(route('messages.destroy', $message))->assertForbidden();

    $this->actingAs($this->student)->deleteJson(route('messages.destroy', $message))->assertOk();
    $this->actingAs($this->moderator)->deleteJson(route('messages.destroy', $another))->assertOk();
    $this->actingAs($this->admin)->deleteJson(route('messages.destroy', $third))->assertOk();

    expect(Message::count())->toBe(0)->and(Message::withTrashed()->count())->toBe(3);
});

test('students can report messages in their channels', function () {
    $message = Message::factory()->create(['channel_id' => $this->own->id, 'user_id' => $this->classmate->id]);

    $this->actingAs($this->student)->post(route('reports.store'), ['type' => 'message', 'id' => $message->id, 'reason' => 'Обидно съобщение'])
        ->assertSessionHasNoErrors();
    $this->actingAs($this->student)->post(route('reports.store'), ['type' => 'message', 'id' => $message->id, 'reason' => 'Пак обидно'])
        ->assertSessionHasErrors('reason');

    expect(Report::where('reportable_type', 'message')->count())->toBe(1);
});
