<?php

namespace App\Policies;

use App\Models\Channel;
use App\Models\ChannelMute;
use App\Models\User;

/**
 * Channels are student-only spaces. Teachers are ALWAYS denied, including read access
 * (also enforced by the `no-teachers` middleware and Channel::visibleTo()).
 */
class ChannelPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isTeacher() && $user->is_active;
    }

    public function view(User $user, Channel $channel): bool
    {
        return $this->viewAny($user)
            && $channel->isVisibleTo($user)
            && (! $channel->is_archived || $user->isAdmin());
    }

    public function post(User $user, Channel $channel): bool
    {
        return $this->view($user, $channel)
            && ! $channel->is_archived
            && ChannelMute::activeFor($user, $channel) === null;
    }

    /**
     * Delete any message and mute users: moderators and admins.
     */
    public function moderate(User $user, Channel $channel): bool
    {
        return ($user->isAdmin() || $user->isModerator()) && $this->view($user, $channel);
    }
}
