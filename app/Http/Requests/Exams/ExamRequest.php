<?php

namespace App\Http\Requests\Exams;

use App\Enums\ExamType;
use App\Models\Exam;
use App\Rules\AudienceScopeRule;
use App\Support\Audience;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create/update an exam. `scope` is encoded as "type:id" (see Audience::options()).
 */
class ExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        $exam = $this->route('exam');

        return $exam instanceof Exam
            ? (bool) $this->user()?->can('update', $exam)
            : (bool) $this->user()?->can('create', Exam::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $anyScope = $this->user()->can('chooseAnyScope', Exam::class);

        return [
            'subject' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'exam_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before:2100-01-01'],
            'type' => ['required', Rule::enum(ExamType::class)],
            'scope' => ['required', 'string', new AudienceScopeRule($anyScope || $this->keepsCurrentScope() ? null : $this->user())],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function examData(): array
    {
        [$type, $id] = Audience::decode($this->validated('scope'));

        return [
            ...$this->safe()->only(['subject', 'title', 'description', 'exam_date', 'type']),
            'scope_type' => $type->value,
            'scope_id' => $id,
        ];
    }

    /**
     * Moderators editing someone else's exam may keep its existing audience.
     */
    private function keepsCurrentScope(): bool
    {
        $exam = $this->route('exam');

        return $exam instanceof Exam && $this->input('scope') === $exam->scope_type?->value.':'.$exam->scope_id;
    }

    public function attributes(): array
    {
        return [
            'subject' => __('ui.exams.subject'),
            'title' => __('ui.exams.title_field'),
            'description' => __('ui.exams.description'),
            'exam_date' => __('ui.exams.date'),
            'type' => __('ui.exams.type'),
            'scope' => __('ui.scope.label'),
        ];
    }
}
