<?php

namespace App\Policies;

use App\Models\Club;
use App\Models\User;

/**
 * Everyone (including teachers) can browse clubs; only admins manage them.
 */
class ClubPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Club $club): bool
    {
        return $club->is_active || $user->isAdmin() || $club->leader_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Club $club): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Club $club): bool
    {
        return $user->isAdmin();
    }

    /**
     * Teachers can view but never join.
     */
    public function join(User $user, Club $club): bool
    {
        return ! $user->isTeacher() && $club->is_active;
    }

    public function leave(User $user, Club $club): bool
    {
        return ! $user->isTeacher();
    }

    /**
     * Create/edit events of this club: admins and the club's own leader.
     */
    public function manageEvents(User $user, Club $club): bool
    {
        return $user->isAdmin() || ($club->leader_id === $user->id && $club->is_active);
    }
}
