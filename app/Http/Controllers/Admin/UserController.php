<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Models\Answer;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\Material;
use App\Models\Message;
use App\Models\Question;
use App\Models\Report;
use App\Models\User;
use App\Services\UserSuspension;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::enum(Role::class)],
            'status' => ['nullable', Rule::in(['active', 'suspended'])],
        ]);

        $users = User::query()
            ->with('schoolClass')
            ->when($filters['q'] ?? null, function (Builder $query, string $term) {
                $like = '%'.addcslashes($term, '\\%_').'%';
                $query->where(fn ($q) => $q->where('name', 'ilike', $like)->orWhere('email', 'ilike', $like));
            })
            ->when($filters['role'] ?? null, fn ($q, $role) => $q->where('role', $role))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('is_active', $status === 'active'))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'class_name' => $user->schoolClass?->name,
                'is_active' => $user->is_active,
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => [
                'q' => $filters['q'] ?? '',
                'role' => $filters['role'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
            'roles' => Role::values(),
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        $user->load('schoolClass', 'allowedStudent');
        $self = $request->user()->is($user);

        $reports = Report::query()
            ->whereHasMorph('reportable', [Question::class, Answer::class, Message::class], fn ($q) => $q->withTrashed()->where('user_id', $user->id))
            ->with('reporter:id,name')
            ->latest('id')
            ->limit(20)
            ->get();

        return Inertia::render('Admin/Users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'class_name' => $user->schoolClass?->name,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at->toIso8601String(),
                'whitelisted' => $user->allowedStudent !== null,
            ],
            'counts' => [
                'questions' => Question::withTrashed()->where('user_id', $user->id)->count(),
                'answers' => Answer::withTrashed()->where('user_id', $user->id)->count(),
                'messages' => Message::withTrashed()->where('user_id', $user->id)->count(),
                'exams' => Exam::where('created_by', $user->id)->count(),
                'materials' => Material::where('user_id', $user->id)->count(),
                'reports_filed' => Report::where('reporter_id', $user->id)->count(),
                'reports_received' => $reports->count(),
            ],
            'reports' => $reports->map(fn (Report $report) => [
                'id' => $report->id,
                'type' => $report->reportable_type,
                'reason' => $report->reason,
                'status' => $report->status->value,
                'reporter' => $report->reporter?->name,
                'created_at' => $report->created_at->toIso8601String(),
            ]),
            'mutes' => $user->mutes()->with('channel:id,name')->latest('id')->limit(10)->get()->map(fn ($mute) => [
                'id' => $mute->id,
                'channel' => $mute->channel?->name,
                'until' => $mute->muted_until->toIso8601String(),
                'active' => $mute->muted_until->isFuture(),
                'reason' => $mute->reason,
            ]),
            'roles' => Role::values(),
            'can' => ['manage' => ! $self],
        ]);
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        $from = $user->role->value;
        $user->update(['role' => $request->validated('role')]);

        AuditLog::record($request->user(), 'user_role_changed', $user, ['from' => $from, 'to' => $user->role->value]);

        return back()->with('success', __('ui.admin.users.role_updated'));
    }

    public function suspend(Request $request, User $user, UserSuspension $suspension): RedirectResponse
    {
        abort_if($request->user()->is($user), 403);

        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:500']])['reason'] ?? null;

        if ($user->is_active) {
            $suspension->suspend($user, $request->user(), $reason);
        }

        return back()->with('success', __('ui.admin.users.suspended_done'));
    }

    public function reactivate(Request $request, User $user, UserSuspension $suspension): RedirectResponse
    {
        abort_if($request->user()->is($user), 403);

        if (! $user->is_active) {
            $suspension->reactivate($user, $request->user());
        }

        return back()->with('success', __('ui.admin.users.reactivated_done'));
    }
}
