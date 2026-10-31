<?php

namespace App\Http\Controllers\Clubs;

use App\Enums\RsvpStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clubs\EventRequest;
use App\Models\Club;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = $request->validate([
            'club' => ['nullable', 'string', 'max:140'],
            'past' => ['nullable', 'boolean'],
        ]);
        $past = (bool) ($filters['past'] ?? false);
        $clubFilter = $filters['club'] ?? null;

        $events = Event::query()
            ->where(fn ($q) => $q->whereNull('club_id')->orWhereHas('club', fn ($q) => $q->active()))
            ->when($clubFilter === 'school', fn ($q) => $q->whereNull('club_id'))
            ->when($clubFilter && $clubFilter !== 'school', fn ($q) => $q->whereHas('club', fn ($q) => $q->where('slug', $clubFilter)))
            ->when($past, fn ($q) => $q->past(), fn ($q) => $q->upcoming())
            ->with('club:id,name,slug')
            ->withRsvpSummary($user)
            ->orderBy('starts_at', $past ? 'desc' : 'asc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Event $event) => self::summary($event));

        return Inertia::render('Events/Index', [
            'events' => $events,
            'filters' => ['club' => $clubFilter ?? '', 'past' => $past],
            'clubs' => Club::query()->active()->orderBy('name')->get(['name', 'slug']),
            'can' => ['create' => $user->can('create', Event::class)],
        ]);
    }

    public function show(Request $request, Event $event): Response
    {
        Gate::authorize('view', $event);

        $user = $request->user();
        $event = Event::query()->whereKey($event->id)->with(['club:id,name,slug,leader_id', 'creator:id,name'])->withRsvpSummary($user)->firstOrFail();

        return Inertia::render('Events/Show', [
            'event' => [
                ...self::summary($event),
                'description' => $event->description,
                'creator' => $event->creator?->name,
                'can' => [
                    'update' => $user->can('update', $event),
                    'delete' => $user->can('delete', $event),
                    'rsvp' => $user->can('rsvp', $event),
                ],
            ],
            'statuses' => RsvpStatus::values(),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Event::class);

        $club = $request->string('club')->toString();

        return Inertia::render('Events/Form', [
            'event' => null,
            'defaultClubId' => $club ? Club::query()->where('slug', $club)->value('id') : null,
            ...$this->formOptions($request->user()),
        ]);
    }

    public function store(EventRequest $request): RedirectResponse
    {
        $event = Event::create([...$request->validated(), 'created_by' => $request->user()->id]);

        return redirect()->route('events.show', $event)->with('success', __('ui.events.created'));
    }

    public function edit(Request $request, Event $event): Response
    {
        Gate::authorize('update', $event);

        return Inertia::render('Events/Form', [
            'event' => [
                'id' => $event->id,
                'club_id' => $event->club_id,
                'title' => $event->title,
                'description' => $event->description ?? '',
                'starts_at' => $event->starts_at->format('Y-m-d\TH:i'),
                'ends_at' => $event->ends_at?->format('Y-m-d\TH:i') ?? '',
                'location' => $event->location,
            ],
            'defaultClubId' => null,
            ...$this->formOptions($request->user(), $event),
        ]);
    }

    public function update(EventRequest $request, Event $event): RedirectResponse
    {
        $event->update($request->validated());

        return redirect()->route('events.show', $event)->with('success', __('ui.events.updated'));
    }

    public function destroy(Event $event): RedirectResponse
    {
        Gate::authorize('delete', $event);

        $club = $event->club;
        $event->delete();

        return redirect($club ? route('clubs.show', $club) : route('events.index'))->with('success', __('ui.events.deleted'));
    }

    /**
     * Set (or change) the user's RSVP.
     */
    public function rsvp(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('rsvp', $event);

        $status = $request->validate(['status' => ['required', Rule::enum(RsvpStatus::class)]])['status'];
        $event->attendees()->syncWithoutDetaching([$request->user()->id => ['status' => $status]]);

        return back()->with('success', __('ui.events.rsvp_saved'));
    }

    public function cancelRsvp(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('rsvp', $event);

        $event->attendees()->detach($request->user()->id);

        return back()->with('success', __('ui.events.rsvp_cancelled'));
    }

    /**
     * @return array<string, mixed>
     */
    public static function summary(Event $event): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'excerpt' => str((string) $event->description)->squish()->limit(140)->toString(),
            'starts_at' => $event->starts_at->toIso8601String(),
            'ends_at' => $event->ends_at?->toIso8601String(),
            'location' => $event->location,
            'club' => $event->relationLoaded('club') && $event->club ? ['name' => $event->club->name, 'slug' => $event->club->slug] : null,
            'going_count' => $event->going_count ?? 0,
            'interested_count' => $event->interested_count ?? 0,
            'my_rsvp' => $event->my_rsvp ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(User $user, ?Event $event = null): array
    {
        $clubs = Club::query()
            ->unless($user->isAdmin(), fn ($q) => $q->where('leader_id', $user->id)->active())
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($event?->club && ! $clubs->contains('id', $event->club_id)) {
            $clubs->push($event->club->only(['id', 'name']));
        }

        return [
            'clubs' => $clubs->map(fn ($club) => ['id' => $club['id'], 'name' => $club['name']])->values(),
            'canSchoolWide' => $user->can('createFor', [Event::class, null]),
        ];
    }
}
