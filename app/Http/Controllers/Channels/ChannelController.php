<?php

namespace App\Http\Controllers\Channels;

use App\Enums\ScopeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Channels\MuteRequest;
use App\Models\Channel;
use App\Models\ChannelMute;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ChannelController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Channel::class);

        return Inertia::render('Channels/Show', [
            'channels' => $this->sidebar($request->user()),
            'channel' => null,
        ]);
    }

    public function show(Request $request, Channel $channel): Response
    {
        Gate::authorize('view', $channel);

        $user = $request->user();
        $canModerate = $user->can('moderate', $channel);
        $page = MessageController::page($channel, $user);

        return Inertia::render('Channels/Show', [
            'channels' => $this->sidebar($user),
            'channel' => [
                'id' => $channel->id,
                'name' => $channel->name,
                'type' => $channel->scope_type->value,
                'is_archived' => $channel->is_archived,
                'can' => ['post' => $user->can('post', $channel), 'moderate' => $canModerate],
            ],
            ...$page,
            'mutes' => $canModerate ? $this->activeMutes($channel) : [],
            'muteDurations' => MuteRequest::DURATIONS,
            'pollSeconds' => (int) config('school.channel_poll_seconds'),
            'maxLength' => Message::MAX_LENGTH,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sidebar(User $user): array
    {
        $order = array_flip([ScopeType::SchoolClass->value, ScopeType::Grade->value, ScopeType::Profile->value]);

        return Channel::query()
            ->visibleTo($user)
            ->unless($user->isAdmin(), fn ($q) => $q->where('is_archived', false))
            ->orderBy('name')
            ->get(['id', 'name', 'scope_type', 'is_archived'])
            ->sortBy(fn (Channel $c) => $order[$c->scope_type->value])
            ->map(fn (Channel $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'type' => $c->scope_type->value,
                'is_archived' => $c->is_archived,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function activeMutes(Channel $channel): array
    {
        return ChannelMute::query()
            ->active()
            ->appliesTo($channel)
            ->with('user:id,name')
            ->orderBy('muted_until')
            ->get()
            ->map(fn (ChannelMute $mute) => [
                'id' => $mute->id,
                'user' => $mute->user?->name,
                'until' => $mute->muted_until->toIso8601String(),
                'reason' => $mute->reason,
                'global' => $mute->isGlobal(),
            ])
            ->all();
    }
}
