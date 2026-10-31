<?php

namespace App\Http\Requests\Clubs;

use App\Models\Club;
use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event instanceof Event
            ? (bool) $this->user()?->can('update', $event)
            : (bool) $this->user()?->can('create', Event::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'club_id' => ['nullable', 'integer', 'exists:clubs,id'],
            'title' => ['required', 'string', 'min:3', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'location' => ['required', 'string', 'max:200'],
        ];
    }

    /**
     * The club (or school-wide) must be one the user is allowed to manage.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('club_id')) {
                    return;
                }

                $club = $this->filled('club_id') ? Club::find($this->integer('club_id')) : null;

                if (! $this->user()->can('createFor', [Event::class, $club])) {
                    $validator->errors()->add('club_id', __('ui.events.club_not_allowed'));
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'club_id' => __('ui.events.club'),
            'title' => __('ui.events.title_field'),
            'description' => __('ui.events.description'),
            'starts_at' => __('ui.events.starts_at'),
            'ends_at' => __('ui.events.ends_at'),
            'location' => __('ui.events.location'),
        ];
    }
}
