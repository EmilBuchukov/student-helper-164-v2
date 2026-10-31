<?php

use App\Enums\Role;
use App\Models\AllowedStudent;
use App\Models\AuditLog;
use App\Models\Question;
use App\Models\Report;
use App\Models\SchoolClass;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->class = SchoolClass::factory()->named(10, 'Б')->create();
    $this->admin = User::factory()->admin()->create();
    $this->moderator = User::factory()->moderator()->inClass($this->class)->create();
    $this->student = User::factory()->inClass($this->class)->create(['name' => 'Иван Петров', 'email' => 'ivan@example.com']);
    $this->teacher = User::factory()->teacher()->create();
});

test('admin-only routes return 403 for students, moderators and teachers', function () {
    $question = Question::factory()->create();
    $question->delete();
    $entry = AllowedStudent::factory()->create(['class_id' => $this->class->id]);

    $requests = [
        ['get', route('admin.dashboard')],
        ['get', route('admin.users.index')],
        ['get', route('admin.users.show', $this->student)],
        ['patch', route('admin.users.role', $this->student), ['role' => 'moderator']],
        ['post', route('admin.users.suspend', $this->student)],
        ['post', route('admin.users.reactivate', $this->student)],
        ['get', route('admin.whitelist.index')],
        ['post', route('admin.whitelist.store'), ['email' => 'new@example.com', 'class_id' => $this->class->id]],
        ['delete', route('admin.whitelist.destroy', $entry)],
        ['get', route('admin.deleted.index')],
        ['post', route('admin.deleted.restore', ['type' => 'question', 'id' => $question->id])],
        ['get', route('admin.audit.index')],
    ];

    foreach ([$this->student, $this->moderator, $this->teacher] as $user) {
        foreach ($requests as $request) {
            [$method, $url] = $request;
            $this->actingAs($user)->{$method}($url, $request[2] ?? [])->assertForbidden();
        }
    }

    expect($this->student->fresh()->is_active)->toBeTrue()
        ->and($this->student->fresh()->role)->toBe(Role::Student)
        ->and(Question::onlyTrashed()->count())->toBe(1)
        ->and(AllowedStudent::count())->toBe(1);
});

test('the dashboard shows counts', function () {
    Report::factory()->count(2)->create();

    $this->actingAs($this->admin)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Dashboard')
            ->where('counts.open_reports', 2)
            ->where('counts.questions', 2)
            ->has('counts.active_mutes'));
});

test('admins search and filter users and open the detail page', function () {
    $this->actingAs($this->admin)->get('/admin/users?q=ivan')
        ->assertInertia(fn (Assert $page) => $page->has('users.data', 1)->where('users.data.0.id', $this->student->id));
    $this->actingAs($this->admin)->get('/admin/users?q=Петров')
        ->assertInertia(fn (Assert $page) => $page->has('users.data', 1));
    $this->actingAs($this->admin)->get('/admin/users?role=teacher')
        ->assertInertia(fn (Assert $page) => $page->has('users.data', 1)->where('users.data.0.id', $this->teacher->id));

    $question = Question::factory()->by($this->student)->create();
    Report::factory()->create(['reportable_id' => $question->id, 'reporter_id' => $this->moderator->id]);

    $this->actingAs($this->admin)->get(route('admin.users.show', $this->student))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Users/Show')
            ->where('counts.questions', 1)
            ->where('counts.reports_received', 1)
            ->has('reports', 1));
});

test('only admins change roles, never their own, and it is audited', function () {
    $this->actingAs($this->admin)->patch(route('admin.users.role', $this->student), ['role' => 'moderator'])->assertRedirect();
    expect($this->student->fresh()->role)->toBe(Role::Moderator);

    $this->actingAs($this->admin)->patch(route('admin.users.role', $this->admin), ['role' => 'student'])->assertForbidden();
    $this->actingAs($this->admin)->patch(route('admin.users.role', $this->student), ['role' => 'superuser'])->assertSessionHasErrors('role');

    $log = AuditLog::where('action', 'user_role_changed')->sole();
    expect($log->user_id)->toBe($this->admin->id)
        ->and($log->target_id)->toBe($this->student->id)
        ->and($log->details)->toMatchArray(['from' => 'student', 'to' => 'moderator']);
});

test('suspending a user blocks login and ends active sessions; reactivation restores access', function () {
    $this->actingAs($this->student)->get('/dashboard')->assertOk();

    $this->actingAs($this->admin)->post(route('admin.users.suspend', $this->student), ['reason' => 'Тормоз'])->assertRedirect();
    expect($this->student->fresh()->is_active)->toBeFalse();

    // Existing session: next request logs the user out.
    $this->actingAs($this->student->fresh())->get('/dashboard')->assertRedirect(route('login'));
    $this->assertGuest();

    $this->post('/login', ['email' => 'ivan@example.com', 'password' => 'password'])->assertSessionHasErrors('email');
    $this->assertGuest();

    $this->actingAs($this->admin)->post(route('admin.users.reactivate', $this->student))->assertRedirect();
    auth()->logout();
    $this->post('/login', ['email' => 'ivan@example.com', 'password' => 'password'])->assertRedirect();
    $this->assertAuthenticatedAs($this->student->fresh());

    $this->actingAs($this->admin)->post(route('admin.users.suspend', $this->admin))->assertForbidden();

    expect(AuditLog::where('action', 'user_suspended')->first()->details['reason'])->toBe('Тормоз')
        ->and(AuditLog::where('action', 'user_reactivated')->count())->toBe(1);
});

test('whitelist: list with claimed status, add and remove single entries', function () {
    AllowedStudent::factory()->create(['email' => 'claimed@example.com', 'class_id' => $this->class->id, 'claimed_at' => now(), 'claimed_by' => $this->student->id]);

    $this->actingAs($this->admin)->post(route('admin.whitelist.store'), ['email' => ' New.Student@Example.com ', 'class_id' => $this->class->id])
        ->assertSessionHasNoErrors();
    $this->actingAs($this->admin)->post(route('admin.whitelist.store'), ['email' => 'new.student@example.com', 'class_id' => $this->class->id])
        ->assertSessionHasErrors('email');
    $this->actingAs($this->admin)->post(route('admin.whitelist.store'), ['email' => 'ivan@example.com', 'class_id' => $this->class->id])
        ->assertSessionHasErrors('email');

    $this->actingAs($this->admin)->get('/admin/whitelist?status=pending')
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Whitelist/Index')
            ->has('entries.data', 1)
            ->where('entries.data.0.email', 'new.student@example.com')
            ->where('stats.claimed', 1));

    $claimed = AllowedStudent::where('email', 'claimed@example.com')->first();
    $this->actingAs($this->admin)->delete(route('admin.whitelist.destroy', $claimed))->assertSessionHas('error');
    $this->actingAs($this->admin)->delete(route('admin.whitelist.destroy', AllowedStudent::where('email', 'new.student@example.com')->first()))
        ->assertSessionHas('success');

    expect(AllowedStudent::pluck('email')->all())->toBe(['claimed@example.com'])
        ->and(AuditLog::whereIn('action', ['whitelist_added', 'whitelist_removed'])->count())->toBe(2);
});

test('admins restore soft-deleted content from the deleted content page', function () {
    $question = Question::factory()->by($this->student)->create(['title' => 'Изтрит въпрос за тест']);
    $this->actingAs($this->moderator)->delete(route('questions.destroy', $question));

    $this->actingAs($this->admin)->get('/admin/deleted?type=question')
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Deleted/Index')->has('items.data', 1)->where('items.data.0.text', 'Изтрит въпрос за тест'));

    $this->actingAs($this->admin)->post(route('admin.deleted.restore', ['type' => 'question', 'id' => $question->id]))->assertRedirect();
    expect($question->fresh()->trashed())->toBeFalse();

    $this->actingAs($this->admin)->post(route('admin.deleted.restore', ['type' => 'user', 'id' => 1]))->assertNotFound();

    expect(AuditLog::where('action', 'content_deleted')->where('user_id', $this->moderator->id)->count())->toBe(1)
        ->and(AuditLog::where('action', 'content_restored')->count())->toBe(1);
});

test('deleting own content is not audited as moderation', function () {
    $question = Question::factory()->by($this->student)->create();
    $this->actingAs($this->student)->delete(route('questions.destroy', $question));

    expect(AuditLog::count())->toBe(0);
});

test('admins see the audit log with action filter', function () {
    AuditLog::record($this->admin, 'user_suspended', $this->student);
    AuditLog::record($this->moderator, 'report_dismissed');

    $this->actingAs($this->admin)->get('/admin/audit')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Audit/Index')
            ->has('logs.data', 2)
            ->where('logs.data.0.action', 'report_dismissed')
            ->where('logs.data.1.label', 'Иван Петров (ivan@example.com)'));

    $this->actingAs($this->admin)->get('/admin/audit?action=user_suspended')
        ->assertInertia(fn (Assert $page) => $page->has('logs.data', 1));
});
