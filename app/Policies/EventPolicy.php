<?php

namespace App\Policies;

use App\Models\Club;
use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Event $event): bool
    {
        return $event->club === null || (new ClubPolicy)->view($user, $event->club);
    }

    /**
     * Admins, or leaders of at least one active club.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->ledClubs()->active()->exists();
    }

    /**
     * Whether the user may create an event for the given club (null = school-wide).
     */
    public function createFor(User $user, ?Club $club): bool
    {
        if ($club === null) {
            return $user->isAdmin();
        }

        return (new ClubPolicy)->manageEvents($user, $club);
    }

    public function update(User $user, Event $event): bool
    {
        return $this->createFor($user, $event->club);
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }

    /**
     * Mark going/interested. Teachers can view events but not RSVP.
     */
    public function rsvp(User $user, Event $event): bool
    {
        return ! $user->isTeacher() && $this->view($user, $event);
    }
}
