<?php

namespace App\Policies;

use App\Models\Answer;
use App\Models\User;

class AnswerPolicy
{
    public function view(User $user, Answer $answer): bool
    {
        return $answer->question !== null && $answer->question->isVisibleTo($user);
    }

    public function update(User $user, Answer $answer): bool
    {
        return ! $user->isTeacher() && $answer->user_id === $user->id;
    }

    public function delete(User $user, Answer $answer): bool
    {
        if ($user->isTeacher()) {
            return false;
        }

        return $answer->user_id === $user->id || $user->isAdmin() || $user->isModerator();
    }

    public function vote(User $user, Answer $answer): bool
    {
        return $this->view($user, $answer) && $answer->user_id !== $user->id;
    }

    public function report(User $user, Answer $answer): bool
    {
        return $this->view($user, $answer) && $answer->user_id !== $user->id;
    }
}
