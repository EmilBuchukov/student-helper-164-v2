<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Toggleable upvotes (questions, answers).
 */
trait Votable
{
    /**
     * @return MorphMany<Vote, $this>
     */
    public function votes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'votable');
    }

    /**
     * Add the user's vote, or remove it if it exists. Returns whether the item is now voted.
     */
    public function toggleVote(User $user): bool
    {
        $deleted = $this->votes()->where('user_id', $user->id)->delete();

        if ($deleted) {
            return false;
        }

        $this->votes()->firstOrCreate(['user_id' => $user->id]);

        return true;
    }
}
