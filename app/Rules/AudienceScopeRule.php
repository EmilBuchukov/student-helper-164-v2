<?php

namespace App\Rules;

use App\Models\User;
use App\Support\Audience;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates an encoded audience value ("class:5"): the target must exist and,
 * when $member is given, that user must belong to it.
 */
class AudienceScopeRule implements ValidationRule
{
    public function __construct(private readonly ?User $member = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $decoded = Audience::decode(is_string($value) ? $value : null);

        if (! $decoded) {
            $fail(__('ui.scope.invalid'));

            return;
        }

        [$type, $id] = $decoded;

        if (! $type->modelClass()::query()->whereKey($id)->exists()) {
            $fail(__('ui.scope.invalid'));

            return;
        }

        if ($this->member && ! Audience::userBelongsTo($this->member, $type, $id)) {
            $fail(__('ui.scope.not_member'));
        }
    }
}
