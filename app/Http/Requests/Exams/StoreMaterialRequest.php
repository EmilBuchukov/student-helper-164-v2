<?php

namespace App\Http\Requests\Exams;

use App\Models\Material;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', [Material::class, $this->route('exam')]);
    }

    /**
     * Both the detected MIME type (`mimes`) and the client extension (`extensions`) are checked.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $extensions = implode(',', config('school.materials_extensions'));

        return [
            'title' => ['required', 'string', 'max:255'],
            'file' => [
                'required',
                'file',
                'mimes:'.$extensions,
                'extensions:'.$extensions,
                'max:'.config('school.materials_max_kb'),
            ],
        ];
    }

    /**
     * The detected content type must match the extension (e.g. a PNG renamed to .txt is rejected).
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $file = $this->file('file');

                if ($validator->errors()->has('file') || ! $file) {
                    return;
                }

                $normalize = fn (?string $ext) => $ext === 'jpeg' ? 'jpg' : strtolower((string) $ext);

                if ($normalize($file->guessExtension()) !== $normalize($file->getClientOriginalExtension())) {
                    $validator->errors()->add('file', __('ui.materials.type_mismatch'));
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => __('ui.materials.title_field'),
            'file' => __('ui.materials.file'),
        ];
    }
}
