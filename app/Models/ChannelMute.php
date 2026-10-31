<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['channel_id', 'user_id', 'muted_until', 'reason', 'created_by'])]
class ChannelMute extends Model
{
    /**
     * @return BelongsTo<Channel, $this>
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isGlobal(): bool
    {
        return $this->channel_id === null;
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('muted_until', '>', now());
    }

    /**
     * Mutes that apply to the channel: its own plus global ones.
     *
     * @param  Builder<static>  $query
     */
    public function scopeAppliesTo(Builder $query, Channel $channel): void
    {
        $query->where(fn (Builder $q) => $q->where('channel_id', $channel->id)->orWhereNull('channel_id'));
    }

    /**
     * The longest active mute of the user in the channel (or globally).
     */
    public static function activeFor(User $user, Channel $channel): ?self
    {
        return static::query()->active()->appliesTo($channel)
            ->where('user_id', $user->id)
            ->orderByDesc('muted_until')
            ->first();
    }

    protected function casts(): array
    {
        return ['muted_until' => 'datetime'];
    }
}
