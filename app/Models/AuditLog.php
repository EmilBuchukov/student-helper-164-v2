<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Append-only log of moderation and admin actions (who, what, target, when).
 */
#[Fillable(['user_id', 'action', 'target_type', 'target_id', 'details', 'ip_address'])]
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    public const ACTIONS = [
        'content_deleted', 'content_restored',
        'report_dismissed', 'report_resolved',
        'user_muted', 'user_unmuted', 'user_suspended', 'user_reactivated', 'user_role_changed',
        'whitelist_added', 'whitelist_removed',
    ];

    /**
     * @param  array<string, mixed>  $details
     */
    public static function record(?User $actor, string $action, ?Model $target = null, array $details = []): self
    {
        return static::create([
            'user_id' => $actor?->id,
            'action' => $action,
            'target_type' => $target?->getMorphClass(),
            'target_id' => $target?->getKey(),
            'details' => array_filter(['label' => $target ? static::labelFor($target) : null, ...$details], fn ($v) => $v !== null),
            'ip_address' => request()?->ip(),
        ]);
    }

    /**
     * Log a deletion done by someone other than the content's owner (i.e. moderation).
     */
    public static function recordModeratorDeletion(Model $target, string $ownerKey = 'user_id'): void
    {
        $actor = auth()->user();

        if ($actor instanceof User && $target->getAttribute($ownerKey) !== $actor->id) {
            static::record($actor, 'content_deleted', $target);
        }
    }

    /**
     * Human-readable snapshot of the target, kept even if it is deleted later.
     */
    public static function labelFor(Model $target): string
    {
        return match (true) {
            $target instanceof Question, $target instanceof Exam, $target instanceof Material => (string) $target->title,
            $target instanceof Answer, $target instanceof Message => str($target->body)->squish()->limit(80)->toString(),
            $target instanceof User => "{$target->name} ({$target->email})",
            $target instanceof AllowedStudent => (string) $target->email,
            $target instanceof ChannelMute => (string) $target->user?->name,
            default => class_basename($target).' #'.$target->getKey(),
        };
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
