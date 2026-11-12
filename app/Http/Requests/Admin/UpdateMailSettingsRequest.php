<?php

namespace App\Http\Requests\Admin;

use App\Services\SiteSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Outgoing mail (SMTP). An empty password keeps the saved one.
 */
class UpdateMailSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('settings.edit');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'mail_mailer' => ['required', Rule::in(SiteSettings::MAILERS)],
            'mail_host' => ['required_if:mail_mailer,smtp', 'nullable', 'string', 'max:255'],
            'mail_port' => ['required_if:mail_mailer,smtp', 'nullable', 'integer', 'between:1,65535'],
            'mail_encryption' => ['required', Rule::in(SiteSettings::ENCRYPTIONS)],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_from_address' => ['required', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
