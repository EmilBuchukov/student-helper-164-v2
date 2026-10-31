<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    public function delete(User $user, Message $message): bool
    {
        $channels = new ChannelPolicy;

        if (! $message->channel || ! $channels->view($user, $message->channel)) {
            return false;
        }

        return $message->user_id === $user->id || $channels->moderate($user, $message->channel);
    }

    public function report(User $user, Message $message): bool
    {
        return $message->channel !== null
            && (new ChannelPolicy)->view($user, $message->channel)
            && $message->user_id !== $user->id;
    }
}
