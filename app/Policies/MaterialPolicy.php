<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\Material;
use App\Models\User;

class MaterialPolicy
{
    /**
     * Viewing/downloading follows the exam's audience.
     */
    public function view(User $user, Material $material): bool
    {
        return $material->exam->isVisibleTo($user);
    }

    /**
     * Materials are shared by students; teachers can read them but not upload.
     */
    public function create(User $user, Exam $exam): bool
    {
        return ! $user->isTeacher() && $exam->isVisibleTo($user);
    }

    public function delete(User $user, Material $material): bool
    {
        return $material->user_id === $user->id || $user->isAdmin() || $user->isModerator();
    }
}
