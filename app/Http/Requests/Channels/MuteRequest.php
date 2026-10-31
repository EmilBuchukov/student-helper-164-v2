<?php

namespace App\Http\Requests\Channels;

use App\Models\Channel;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MuteRequest extends FormRequest
{
    /**
     * Allowed mute durations in minutes (15 min, 1 h, 1 day, 1 week).
     */
    public const DURATIONS = [15, 60, 1440, 10080];

    public function authorize(): bool
    {
        $channel = $this->route('channel');

        return $channel instanceof Channel && (bool) $this->user()?->can('moderate', $channel);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'minutes' => ['required', 'integer', Rule::in(self::DURATIONS)],
            'reason' => ['nullable', 'string', 'max:500'],
            'global' => ['boolean'],
        ];
    }

    /**
     * Admins cannot be muted; moderators only by admins; nobody mutes themselves.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('user_id')) {
                    return;
                }

                $target = User::find($this->integer('user_id'));
                $actor = $this->user();

                if ($target->is($actor) || $target->isAdmin() || ($target->isModerator() && ! $actor->isAdmin())) {
                    $validator->errors()->add('user_id', __('ui.channels.cannot_mute'));
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'minutes' => __('ui.channels.mute_duration'),
            'reason' => __('ui.channels.mute_reason'),
        ];
    }
}
