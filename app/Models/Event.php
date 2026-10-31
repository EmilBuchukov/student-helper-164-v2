<?php

namespace App\Models;

use App\Enums\RsvpStatus;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['club_id', 'title', 'description', 'starts_at', 'ends_at', 'location', 'created_by'])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Club, $this>
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_attendees')->withPivot('status')->withTimestamps();
    }

    public function isSchoolWide(): bool
    {
        return $this->club_id === null;
    }

    /**
     * Events that have not finished yet.
     *
     * @param  Builder<static>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $now = now();

        $query->where(fn (Builder $q) => $q
            ->where('starts_at', '>=', $now)
            ->orWhere('ends_at', '>=', $now));
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopePast(Builder $query): void
    {
        $now = now();

        $query->where('starts_at', '<', $now)
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '<', $now));
    }

    /**
     * Adds going_count, interested_count and the user's own RSVP status.
     *
     * @param  Builder<static>  $query
     */
    public function scopeWithRsvpSummary(Builder $query, User $user): void
    {
        $query->withCount([
            'attendees as going_count' => fn ($q) => $q->where('event_attendees.status', RsvpStatus::Going),
            'attendees as interested_count' => fn ($q) => $q->where('event_attendees.status', RsvpStatus::Interested),
        ])->addSelect(['my_rsvp' => fn ($q) => $q->select('status')
            ->from('event_attendees')
            ->whereColumn('event_attendees.event_id', 'events.id')
            ->where('event_attendees.user_id', $user->id)
            ->limit(1)]);
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
