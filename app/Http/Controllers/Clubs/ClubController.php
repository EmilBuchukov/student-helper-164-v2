<?php

namespace App\Http\Controllers\Clubs;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clubs\ClubRequest;
use App\Models\Club;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ClubController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $clubs = Club::query()
            ->unless($user->isAdmin(), fn ($q) => $q->where(fn ($q) => $q->active()->orWhere('leader_id', $user->id)))
            ->with('leader:id,name')
            ->withCount(['members', 'events as upcoming_events_count' => fn ($q) => $q->upcoming()])
            ->withExists(['members as is_member' => fn ($q) => $q->whereKey($user->id)])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return Inertia::render('Clubs/Index', [
            'clubs' => $clubs->map(fn (Club $club) => $this->summary($club)),
            'can' => ['create' => $user->can('create', Club::class)],
        ]);
    }

    public function show(Request $request, Club $club): Response
    {
        Gate::authorize('view', $club);

        $user = $request->user();
        $club->load('leader:id,name')->loadCount('members');

        $events = $club->events()
            ->upcoming()
            ->withRsvpSummary($user)
            ->orderBy('starts_at')
            ->limit(20)
            ->get();

        return Inertia::render('Clubs/Show', [
            'club' => [
                ...$this->summary($club),
                'description' => $club->description,
                'is_member' => $club->hasMember($user),
                'can' => [
                    'update' => $user->can('update', $club),
                    'delete' => $user->can('delete', $club),
                    'join' => $user->can('join', $club),
                    'leave' => $user->can('leave', $club),
                    'manage_events' => $user->can('manageEvents', $club),
                ],
            ],
            'events' => $events->map(fn (Event $event) => EventController::summary($event)),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Club::class);

        return Inertia::render('Clubs/Form', ['club' => null, 'leaders' => $this->leaderOptions()]);
    }

    public function store(ClubRequest $request): RedirectResponse
    {
        $club = Club::create($request->validated());

        return redirect()->route('clubs.show', $club)->with('success', __('ui.clubs.created'));
    }

    public function edit(Club $club): Response
    {
        Gate::authorize('update', $club);

        return Inertia::render('Clubs/Form', [
            'club' => $club->only(['id', 'slug', 'name', 'description', 'leader_id', 'is_active']),
            'leaders' => $this->leaderOptions(),
        ]);
    }

    public function update(ClubRequest $request, Club $club): RedirectResponse
    {
        $club->update($request->validated());

        return redirect()->route('clubs.show', $club)->with('success', __('ui.clubs.updated'));
    }

    public function destroy(Club $club): RedirectResponse
    {
        Gate::authorize('delete', $club);

        $club->delete();

        return redirect()->route('clubs.index')->with('success', __('ui.clubs.deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Club $club): array
    {
        return [
            'id' => $club->id,
            'slug' => $club->slug,
            'name' => $club->name,
            'excerpt' => str((string) $club->description)->squish()->limit(140)->toString(),
            'leader' => $club->leader?->name,
            'is_active' => $club->is_active,
            'is_member' => (bool) ($club->is_member ?? false),
            'members_count' => $club->members_count ?? 0,
            'upcoming_events_count' => $club->upcoming_events_count ?? 0,
        ];
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    private function leaderOptions(): array
    {
        return User::query()
            ->whereIn('role', [Role::Student, Role::Moderator])
            ->where('is_active', true)
            ->with('schoolClass')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'label' => $user->schoolClass ? "{$user->name} ({$user->schoolClass->name})" : $user->name,
            ])
            ->all();
    }
}
