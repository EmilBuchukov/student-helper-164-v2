<?php

use App\Enums\ReportStatus;
use App\Models\Answer;
use App\Models\AuditLog;
use App\Models\Channel;
use App\Models\ChannelMute;
use App\Models\Message;
use App\Models\Question;
use App\Models\Report;
use App\Models\SchoolClass;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->class = SchoolClass::factory()->named(10, 'Б')->create();
    $this->admin = User::factory()->admin()->create();
    $this->moderator = User::factory()->moderator()->inClass($this->class)->create();
    $this->author = User::factory()->inClass($this->class)->create();
    $this->reporter = User::factory()->inClass($this->class)->create();
    $this->question = Question::factory()->by($this->author)->create(['title' => 'Купувам отговори']);
    $this->report = Report::create(['reporter_id' => $this->reporter->id, 'reportable_type' => 'question', 'reportable_id' => $this->question->id, 'reason' => 'Преписване']);
});

test('students and teachers cannot access the reports queue; moderators and admins can', function () {
    foreach ([$this->author, User::factory()->teacher()->create()] as $user) {
        $this->actingAs($user)->get(route('moderation.reports.index'))->assertForbidden();
        $this->actingAs($user)->post(route('moderation.reports.dismiss', $this->report))->assertForbidden();
        $this->actingAs($user)->post(route('moderation.reports.resolve', $this->report), ['remove_content' => true])->assertForbidden();
    }

    foreach ([$this->moderator, $this->admin] as $user) {
        $this->actingAs($user)->get(route('moderation.reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Moderation/Reports/Index')
                ->has('reports.data', 1)
                ->where('reports.data.0.content.title', 'Купувам отговори')
                ->where('reports.data.0.content.author', $this->author->name)
                ->where('reports.data.0.reporter', $this->reporter->name));
    }

    expect($this->report->fresh()->status)->toBe(ReportStatus::Open);
});

test('the queue previews answers and messages with links', function () {
    $answer = Answer::factory()->by($this->author)->create(['question_id' => $this->question->id]);
    $channel = Channel::where('scope_type', 'class')->where('scope_id', $this->class->id)->first();
    $message = Message::factory()->create(['channel_id' => $channel->id, 'user_id' => $this->author->id, 'body' => 'Обидно съобщение']);
    Report::create(['reporter_id' => $this->reporter->id, 'reportable_type' => 'answer', 'reportable_id' => $answer->id, 'reason' => 'Грешно']);
    Report::create(['reporter_id' => $this->reporter->id, 'reportable_type' => 'message', 'reportable_id' => $message->id, 'reason' => 'Обида']);

    $this->actingAs($this->moderator)->get('/moderation/reports?type=message')
        ->assertInertia(fn (Assert $page) => $page->has('reports.data', 1)
            ->where('reports.data.0.content.text', 'Обидно съобщение')
            ->where('reports.data.0.content.context', '10Б')
            ->where('reports.data.0.content.url', route('channels.show', $channel)));

    $this->actingAs($this->moderator)->get('/moderation/reports?type=answer')
        ->assertInertia(fn (Assert $page) => $page->where('reports.data.0.content.url', route('questions.show', $this->question)));
});

test('dismissing records the handler and is audited', function () {
    $this->actingAs($this->moderator)->post(route('moderation.reports.dismiss', $this->report), ['note' => 'Няма нарушение'])->assertRedirect();

    $report = $this->report->fresh();
    expect($report->status)->toBe(ReportStatus::Dismissed)
        ->and($report->handled_by)->toBe($this->moderator->id)
        ->and($report->handled_at)->not->toBeNull()
        ->and($report->resolution_note)->toBe('Няма нарушение')
        ->and($this->question->fresh()->trashed())->toBeFalse()
        ->and(AuditLog::where('action', 'report_dismissed')->where('user_id', $this->moderator->id)->count())->toBe(1);

    $this->actingAs($this->moderator)->post(route('moderation.reports.dismiss', $this->report))->assertStatus(409);
});

test('resolving with removal soft-deletes the content, mutes the author and closes other reports', function () {
    $other = Report::create(['reporter_id' => $this->moderator->id, 'reportable_type' => 'question', 'reportable_id' => $this->question->id, 'reason' => 'Спам']);

    $this->actingAs($this->moderator)->post(route('moderation.reports.resolve', $this->report), [
        'remove_content' => true, 'mute_minutes' => 1440, 'note' => 'Нарушение на правилата',
    ])->assertSessionHasNoErrors()->assertRedirect();

    expect(Question::find($this->question->id))->toBeNull()
        ->and(Question::withTrashed()->find($this->question->id)->trashed())->toBeTrue()
        ->and($this->report->fresh()->status)->toBe(ReportStatus::Resolved)
        ->and($this->report->fresh()->handled_by)->toBe($this->moderator->id)
        ->and($other->fresh()->status)->toBe(ReportStatus::Resolved)
        ->and(ChannelMute::active()->where('user_id', $this->author->id)->whereNull('channel_id')->exists())->toBeTrue()
        ->and(AuditLog::pluck('action')->sort()->values()->all())->toBe(['content_deleted', 'report_resolved', 'user_muted']);

    // Reported content stays visible in the queue (soft-deleted).
    $this->actingAs($this->moderator)->get('/moderation/reports?status=resolved')
        ->assertInertia(fn (Assert $page) => $page->has('reports.data', 2)->where('reports.data.0.content.deleted', true));
});

test('only admins can suspend the author when resolving', function () {
    $this->actingAs($this->moderator)->post(route('moderation.reports.resolve', $this->report), ['suspend' => true])->assertForbidden();
    expect($this->author->fresh()->is_active)->toBeTrue();

    $this->actingAs($this->admin)->post(route('moderation.reports.resolve', $this->report), ['suspend' => true, 'remove_content' => true])->assertRedirect();
    expect($this->author->fresh()->is_active)->toBeFalse()
        ->and(AuditLog::where('action', 'user_suspended')->where('target_id', $this->author->id)->exists())->toBeTrue();
});

test('admins cannot be muted or suspended through reports', function () {
    $question = Question::factory()->by($this->admin)->create();
    $report = Report::create(['reporter_id' => $this->reporter->id, 'reportable_type' => 'question', 'reportable_id' => $question->id, 'reason' => 'Не ми харесва']);

    $this->actingAs($this->moderator)->post(route('moderation.reports.resolve', $report), ['mute_minutes' => 60])->assertSessionHasErrors('mute_minutes');

    expect(ChannelMute::count())->toBe(0)->and($report->fresh()->status)->toBe(ReportStatus::Open);
});
