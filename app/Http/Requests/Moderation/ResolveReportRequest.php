<?php

namespace App\Http\Requests\Moderation;

use App\Http\Requests\Channels\MuteRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveReportRequest extends FormRequest
{
    /**
     * Moderators and admins (route middleware). Suspending the author is admin-only.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user?->isAdmin() && ! $user?->isModerator()) {
            return false;
        }

        return ! $this->boolean('suspend') || $user->isAdmin();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'remove_content' => ['boolean'],
            'mute_minutes' => ['nullable', 'integer', Rule::in(MuteRequest::DURATIONS)],
            'suspend' => ['boolean'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
