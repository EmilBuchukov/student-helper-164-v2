<?php

namespace App\Http\Requests\Questions;

use App\Models\Question;
use App\Rules\AudienceScopeRule;
use App\Support\Audience;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Create/update a question. `scope` is empty (school-wide) or encoded as "type:id".
 */
class QuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $question = $this->route('question');

        return $question instanceof Question
            ? (bool) $this->user()?->can('update', $question)
            : (bool) $this->user()?->can('create', Question::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $anyScope = $this->user()->can('chooseAnyScope', Question::class) || $this->keepsCurrentScope();

        return [
            'title' => ['required', 'string', 'min:5', 'max:200'],
            'body' => ['required', 'string', 'max:10000'],
            'subject' => ['nullable', 'string', 'max:100'],
            'scope' => ['nullable', 'string', new AudienceScopeRule($anyScope ? null : $this->user())],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function questionData(): array
    {
        [$type, $id] = Audience::decode($this->validated('scope')) ?? [null, null];

        return [
            ...$this->safe()->only(['title', 'body', 'subject']),
            'scope_type' => $type?->value,
            'scope_id' => $id,
        ];
    }

    private function keepsCurrentScope(): bool
    {
        $question = $this->route('question');

        return $question instanceof Question
            && $question->scope_type !== null
            && $this->input('scope') === $question->scope_type->value.':'.$question->scope_id;
    }

    public function attributes(): array
    {
        return [
            'title' => __('ui.questions.title_field'),
            'body' => __('ui.questions.body'),
            'subject' => __('ui.questions.subject'),
            'scope' => __('ui.scope.label'),
        ];
    }
}
