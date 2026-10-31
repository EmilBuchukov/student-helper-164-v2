<?php

namespace App\Http\Requests\Clubs;

use App\Enums\Role;
use App\Models\Club;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClubRequest extends FormRequest
{
    public function authorize(): bool
    {
        $club = $this->route('club');

        return $club instanceof Club
            ? (bool) $this->user()?->can('update', $club)
            : (bool) $this->user()?->can('create', Club::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            // Leaders are students or moderators (club members themselves).
            'leader_id' => ['nullable', 'integer', Rule::exists('users', 'id')
                ->whereIn('role', [Role::Student->value, Role::Moderator->value])
                ->where('is_active', true)],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('ui.clubs.name'),
            'description' => __('ui.clubs.description'),
            'leader_id' => __('ui.clubs.leader'),
        ];
    }
}
