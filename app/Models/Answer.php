<?php

namespace App\Models;

use App\Models\Concerns\Reportable;
use App\Models\Concerns\Votable;
use Database\Factories\AnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['question_id', 'user_id', 'body'])]
class Answer extends Model
{
    /** @use HasFactory<AnswerFactory> */
    use HasFactory, Reportable, SoftDeletes, Votable;

    protected static function booted(): void
    {
        // A deleted answer can no longer be the accepted one.
        static::deleted(function (Answer $answer) {
            Question::withTrashed()
                ->where('accepted_answer_id', $answer->id)
                ->update(['accepted_answer_id' => null]);
        });
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAccepted(): bool
    {
        return $this->question?->accepted_answer_id === $this->id;
    }
}
