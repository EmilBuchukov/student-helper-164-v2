<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAllowedStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', 'unique:allowed_students,email', 'unique:users,email'],
            'class_id' => ['required', 'integer', 'exists:classes,id'],
        ];
    }

    public function attributes(): array
    {
        return ['email' => __('ui.admin.whitelist.email'), 'class_id' => __('ui.admin.whitelist.class')];
    }
}
