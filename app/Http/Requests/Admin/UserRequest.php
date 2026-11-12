<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

/**
 * Shared rules for creating and updating a user. Access fields (roles,
 * branches, all_branches) are prohibited when the actor may not assign
 * them, e.g. when editing their own account.
 */
abstract class UserRequest extends FormRequest
{
    abstract protected function target(): ?User;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $target = $this->target();
        $canAssign = $this->user()->can('assignRoles', $target ?? User::class);
        $assignable = $canAssign ? 'nullable' : 'prohibited';

        $roleNames = Rule::exists('roles', 'name');

        if (! $this->user()->isSuperAdmin()) {
            $roleNames->whereNot('name', config('permissions.super_admin_role'));
        }

        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($target)],
            'phone' => ['required', 'string', 'regex:/^05\d{8}$/', Rule::unique('users')->ignore($target)],
            'password' => [$target ? 'nullable' : 'required', 'confirmed', Password::defaults()],
            'user_type' => ['required', new Enum(UserType::class)],
            'agency_id' => ['nullable', 'required_if:user_type,'.UserType::ExternalAgency->value, 'exists:agencies,id'],
            'preferred_language' => ['required', Rule::in(array_keys(config('app.available_locales')))],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'must_change_password' => ['boolean'],
            'roles' => [$assignable, 'array'],
            'roles.*' => ['string', $roleNames],
            'branches' => [$assignable, 'array'],
            'branches.*' => ['integer', 'exists:branches,id'],
            'all_branches' => [$assignable, 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower((string) $this->input('email')),
            'must_change_password' => $this->boolean('must_change_password'),
        ]);

        if ($this->has('all_branches')) {
            $this->merge(['all_branches' => $this->boolean('all_branches')]);
        }

        if ($this->input('user_type') !== UserType::ExternalAgency->value) {
            $this->merge(['agency_id' => null]);
        }
    }

    /**
     * Whether the actor may change roles and branches for this request.
     */
    public function canAssignAccess(): bool
    {
        return $this->user()->can('assignRoles', $this->target() ?? User::class);
    }
}
