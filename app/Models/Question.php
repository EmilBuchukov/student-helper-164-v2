<?php

namespace App\Models;

use App\Models\Concerns\HasAudienceScope;
use App\Models\Concerns\Reportable;
use App\Models\Concerns\Votable;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'title', 'body', 'subject', 'scope_type', 'scope_id', 'accepted_answer_id'])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasAudienceScope, HasFactory, Reportable, SoftDeletes, Votable;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Answer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    /**
     * @return BelongsTo<Answer, $this>
     */
    public function acceptedAnswer(): BelongsTo
    {
        return $this->belongsTo(Answer::class, 'accepted_answer_id');
    }

    public function isResolved(): bool
    {
        return $this->accepted_answer_id !== null;
    }

    /**
     * Case-insensitive substring search in title and body (PostgreSQL ILIKE).
     *
     * @param  Builder<static>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $like = '%'.addcslashes($term, '\\%_').'%';

        $query->where(fn (Builder $q) => $q
            ->where('questions.title', 'ilike', $like)
            ->orWhere('questions.body', 'ilike', $like));
    }

    /**
     * Q&A is a student space: teachers never see questions.
     */
    protected static function audienceHiddenFrom(User $user): bool
    {
        return $user->isTeacher();
    }

    /**
     * Questions without a scope are school-wide.
     */
    protected static function audienceIncludesUnscoped(): bool
    {
        return true;
    }
}
