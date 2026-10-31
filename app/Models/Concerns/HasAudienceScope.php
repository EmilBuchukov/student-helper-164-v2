<?php

namespace App\Models\Concerns;

use App\Enums\ScopeType;
use App\Models\User;
use App\Support\Audience;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Shared audience scoping for content targeted at a grade, class or profile
 * (columns `scope_type` + `scope_id`). Used by exams, questions and channels.
 *
 * Models can override:
 *  - audienceSeesAll(): roles that see every scope (default: admins and moderators)
 *  - audienceHiddenFrom(): roles that never see anything (e.g. teachers for channels)
 *  - audienceIncludesUnscoped(): whether rows without a scope are school-wide and visible to all
 */
trait HasAudienceScope
{
    public function initializeHasAudienceScope(): void
    {
        $this->mergeCasts(['scope_type' => ScopeType::class, 'scope_id' => 'integer']);
    }

    /**
     * The grade / class / profile this item targets.
     *
     * @return MorphTo<Model, $this>
     */
    public function scope(): MorphTo
    {
        return $this->morphTo('scope', 'scope_type', 'scope_id');
    }

    /**
     * Only items whose audience includes the user (their class, grade or profile).
     *
     * @param  Builder<static>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $table = $this->getTable();

        if (static::audienceHiddenFrom($user)) {
            $query->whereRaw('1 = 0');

            return;
        }

        if (static::audienceSeesAll($user)) {
            return;
        }

        $scopes = Audience::scopesFor($user);

        $query->where(function (Builder $query) use ($scopes, $table) {
            foreach ($scopes as $type => $id) {
                $query->orWhere(fn (Builder $q) => $q
                    ->where("{$table}.scope_type", $type)
                    ->where("{$table}.scope_id", $id));
            }

            if (static::audienceIncludesUnscoped()) {
                $query->orWhereNull("{$table}.scope_type");
            }

            if ($scopes === [] && ! static::audienceIncludesUnscoped()) {
                $query->whereRaw('1 = 0');
            }
        });
    }

    /**
     * Filter by an exact scope target.
     *
     * @param  Builder<static>  $query
     */
    public function scopeForAudience(Builder $query, ScopeType $type, int $id): void
    {
        $query->where($this->getTable().'.scope_type', $type->value)
            ->where($this->getTable().'.scope_id', $id);
    }

    public function isVisibleTo(User $user): bool
    {
        if (static::audienceHiddenFrom($user)) {
            return false;
        }

        if (static::audienceSeesAll($user)) {
            return true;
        }

        if ($this->scope_type === null) {
            return static::audienceIncludesUnscoped();
        }

        return Audience::userBelongsTo($user, $this->scope_type, $this->scope_id);
    }

    public function isSchoolWide(): bool
    {
        return $this->scope_type === null;
    }

    /**
     * Readable audience, e.g. "Клас: 10Б". Eager load `scope` on lists to avoid N+1 queries.
     */
    public function audienceLabel(): string
    {
        if ($this->scope_type === null) {
            return __('ui.scope.school_wide');
        }

        return $this->scope_type->label().': '.$this->scope_type->describe($this->scope);
    }

    protected static function audienceSeesAll(User $user): bool
    {
        return $user->isAdmin() || $user->isModerator();
    }

    protected static function audienceHiddenFrom(User $user): bool
    {
        return false;
    }

    protected static function audienceIncludesUnscoped(): bool
    {
        return false;
    }
}
