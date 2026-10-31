<?php

namespace App\Support;

use App\Enums\ScopeType;
use App\Models\Concerns\HasAudienceScope;
use App\Models\Grade;
use App\Models\Profile;
use App\Models\SchoolClass;
use App\Models\User;

/**
 * Helpers for the shared audience-scoping mechanism (grade / class / profile).
 *
 * @see HasAudienceScope
 */
class Audience
{
    /**
     * The scopes a user belongs to through their class: [scope_type => scope_id].
     *
     * @return array<string, int>
     */
    public static function scopesFor(User $user): array
    {
        $class = $user->class_id ? $user->schoolClass : null;

        if (! $class) {
            return [];
        }

        return array_filter([
            ScopeType::SchoolClass->value => $class->id,
            ScopeType::Grade->value => $class->grade_id,
            ScopeType::Profile->value => $class->profile_id,
        ]);
    }

    public static function userBelongsTo(User $user, ScopeType|string|null $type, ?int $id): bool
    {
        $type = $type instanceof ScopeType ? $type : ScopeType::tryFrom((string) $type);

        if (! $type || ! $id) {
            return false;
        }

        return (self::scopesFor($user)[$type->value] ?? null) === $id;
    }

    /**
     * Scope choices for a select box, encoded as "type:id" (e.g. "class:5").
     *
     * @return list<array{value: string, type: string, id: int, label: string}>
     */
    public static function options(?User $restrictTo = null): array
    {
        if ($restrictTo) {
            $class = $restrictTo->schoolClass?->loadMissing('profile');

            if (! $class) {
                return [];
            }

            return array_values(array_filter([
                self::option(ScopeType::SchoolClass, $class),
                self::option(ScopeType::Grade, $class->grade),
                $class->profile ? self::option(ScopeType::Profile, $class->profile) : null,
            ]));
        }

        return [
            ...Grade::query()->orderBy('number')->get()->map(fn (Grade $g) => self::option(ScopeType::Grade, $g)),
            ...SchoolClass::query()->ordered()->get()->map(fn (SchoolClass $c) => self::option(ScopeType::SchoolClass, $c)),
            ...Profile::query()->orderBy('name')->get()->map(fn (Profile $p) => self::option(ScopeType::Profile, $p)),
        ];
    }

    /**
     * Parse an encoded "type:id" value.
     *
     * @return array{0: ScopeType, 1: int}|null
     */
    public static function decode(?string $value): ?array
    {
        if (! $value || ! preg_match('/^([a-z]+):(\d+)$/', $value, $m)) {
            return null;
        }

        $type = ScopeType::tryFrom($m[1]);

        return $type ? [$type, (int) $m[2]] : null;
    }

    /**
     * @return array{value: string, type: string, id: int, label: string}
     */
    private static function option(ScopeType $type, Grade|SchoolClass|Profile $target): array
    {
        return [
            'value' => $type->value.':'.$target->id,
            'type' => $type->value,
            'id' => $target->id,
            'label' => $type->label().': '.$type->describe($target),
        ];
    }
}
