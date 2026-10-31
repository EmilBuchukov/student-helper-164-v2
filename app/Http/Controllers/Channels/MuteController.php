<?php

namespace App\Http\Controllers\Channels;

use App\Http\Controllers\Controller;
use App\Http\Requests\Channels\MuteRequest;
use App\Models\AuditLog;
use App\Models\Channel;
use App\Models\ChannelMute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MuteController extends Controller
{
    public function store(MuteRequest $request, Channel $channel): RedirectResponse
    {
        $mute = ChannelMute::create([
            'channel_id' => $request->boolean('global') ? null : $channel->id,
            'user_id' => $request->integer('user_id'),
            'muted_until' => now()->addMinutes($request->integer('minutes')),
            'reason' => $request->validated('reason'),
            'created_by' => $request->user()->id,
        ]);
        AuditLog::record($request->user(), 'user_muted', $mute->user, [
            'until' => $mute->muted_until->toIso8601String(),
            'channel' => $channel->name,
            'global' => $mute->isGlobal(),
            'reason' => $mute->reason,
        ]);

        return back()->with('success', __('ui.channels.muted_user'));
    }

    public function destroy(Request $request, ChannelMute $channelMute): RedirectResponse
    {
        $user = $request->user();
        $allowed = $channelMute->channel
            ? $user->can('moderate', $channelMute->channel)
            : ($user->isAdmin() || $user->isModerator());

        abort_unless($allowed, 403);

        $channelMute->update(['muted_until' => now()]);
        AuditLog::record($user, 'user_unmuted', $channelMute->user);

        return back()->with('success', __('ui.channels.unmuted_user'));
    }
}
