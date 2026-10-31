<?php

namespace App\Http\Requests\Questions;

use App\Models\Answer;
use App\Models\Question;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $answer = $this->route('answer');

        if ($answer instanceof Answer) {
            return (bool) $this->user()?->can('update', $answer);
        }

        $question = $this->route('question');

        return $question instanceof Question && (bool) $this->user()?->can('answer', $question);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:10000'],
        ];
    }

    public function attributes(): array
    {
        return ['body' => __('ui.answers.body')];
    }
}
