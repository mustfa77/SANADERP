<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create / update a branch. The code is stored upper-case.
 */
class BranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $branch = $this->route('branch');

        return $branch instanceof Branch
            ? $this->user()->can('update', $branch)
            : $this->user()->can('create', Branch::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
            'email' => $this->filled('email') ? strtolower(trim((string) $this->input('email'))) : null,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('branches', 'code')->ignore($this->route('branch'))],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'city_ar' => ['nullable', 'string', 'max:255'],
            'city_en' => ['nullable', 'string', 'max:255'],
            'address_ar' => ['nullable', 'string', 'max:500'],
            'address_en' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\s-]{7,20}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'manager_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('user_type', UserType::Internal->value)
                    ->where('status', UserStatus::Active->value)->whereNull('deleted_at'),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * "code" and "phone" have other meanings in the shared attribute names.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code' => __('branches.fields.code'),
            'phone' => __('branches.fields.phone'),
            'name_ar' => __('branches.fields.name_ar'),
            'name_en' => __('branches.fields.name_en'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => __('branches.code_hint'),
        ];
    }
}
