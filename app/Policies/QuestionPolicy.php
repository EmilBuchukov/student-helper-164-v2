<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;

/**
 * Q&A is a student space: every ability is denied to teachers.
 */
class QuestionPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isTeacher();
    }

    public function view(User $user, Question $question): bool
    {
        return $question->isVisibleTo($user);
    }

    public function create(User $user): bool
    {
        return ! $user->isTeacher();
    }

    /**
     * Admins may ask questions for any grade, class or profile; others only for their own.
     */
    public function chooseAnyScope(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Question $question): bool
    {
        return ! $user->isTeacher() && $question->user_id === $user->id;
    }

    public function delete(User $user, Question $question): bool
    {
        if ($user->isTeacher()) {
            return false;
        }

        return $question->user_id === $user->id || $user->isAdmin() || $user->isModerator();
    }

    public function vote(User $user, Question $question): bool
    {
        return $this->view($user, $question) && $question->user_id !== $user->id;
    }

    public function report(User $user, Question $question): bool
    {
        return $this->view($user, $question) && $question->user_id !== $user->id;
    }

    public function accept(User $user, Question $question): bool
    {
        return ! $user->isTeacher() && $question->user_id === $user->id;
    }

    public function answer(User $user, Question $question): bool
    {
        return $this->view($user, $question);
    }
}
