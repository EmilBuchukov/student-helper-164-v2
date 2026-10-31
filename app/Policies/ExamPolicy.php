<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\User;

class ExamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Exam $exam): bool
    {
        return $exam->isVisibleTo($user);
    }

    /**
     * Students and moderators create exams for their own class/grade/profile, so they need a class.
     */
    public function create(User $user): bool
    {
        return $this->chooseAnyScope($user) || $user->class_id !== null;
    }

    /**
     * Admins and teachers may target any grade, class or profile.
     */
    public function chooseAnyScope(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function update(User $user, Exam $exam): bool
    {
        return $user->isAdmin() || $user->isModerator() || $exam->created_by === $user->id;
    }

    public function delete(User $user, Exam $exam): bool
    {
        return $this->update($user, $exam);
    }
}
