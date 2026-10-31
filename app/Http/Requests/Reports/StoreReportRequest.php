<?php

namespace App\Http\Requests\Reports;

use App\Models\Report;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Report a question, answer or message. Authorization uses the target's `report` policy ability.
 */
class StoreReportRequest extends FormRequest
{
    private ?Model $target = null;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(Report::REPORTABLE_TYPES)],
            'id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $target = $this->target();

                abort_unless($target, 404);
                Gate::authorize('report', $target);

                if ($target->hasOpenReportFrom($this->user())) {
                    $validator->errors()->add('reason', __('ui.reports.already_reported'));
                }
            },
        ];
    }

    public function target(): ?Model
    {
        if ($this->target) {
            return $this->target;
        }

        $class = Relation::getMorphedModel((string) $this->input('type'));

        return $this->target = $class ? $class::query()->find((int) $this->input('id')) : null;
    }

    public function attributes(): array
    {
        return ['reason' => __('ui.reports.reason')];
    }
}
