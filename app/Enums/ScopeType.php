<?php

namespace App\Enums;

use App\Models\Grade;
use App\Models\Profile;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Model;

/**
 * Audience a piece of content targets: a whole grade, a single class or a profile (specialization).
 * The values double as morph aliases, so `scope_type` + `scope_id` form a morphTo relation.
 */
enum ScopeType: string
{
    case Grade = 'grade';
    case SchoolClass = 'class';
    case Profile = 'profile';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return class-string<Model>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::Grade => Grade::class,
            self::SchoolClass => SchoolClass::class,
            self::Profile => Profile::class,
        };
    }

    public function label(): string
    {
        return __('ui.scope.types.'.$this->value);
    }

    /**
     * Human readable name of a concrete scope target, e.g. "10 клас", "10Б", "Испански език и информатика".
     */
    public function describe(?Model $target): string
    {
        if (! $target) {
            return __('ui.scope.unknown');
        }

        return match ($this) {
            self::Grade => __('ui.scope.grade_name', ['number' => $target->number]),
            self::SchoolClass => $target->name,
            self::Profile => $target->name,
        };
    }
}
