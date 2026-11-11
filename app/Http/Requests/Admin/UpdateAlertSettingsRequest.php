<?php

namespace App\Http\Requests\Admin;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Alert periods and document upload size (reference data: settings.manage).
 */
class UpdateAlertSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('settings.manage');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $rules = [];

        foreach (Setting::ALERTS as $key => $definition) {
            $rules[$key] = ['required', 'integer', 'min:'.$definition['min'], 'max:'.$definition['max']];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return collect(Setting::ALERTS)->mapWithKeys(fn (array $definition, string $key): array => [$key => __("settings.alerts.fields.{$key}.label")])->all();
    }
}
