<?php

namespace App\Http\Requests\Admin;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Recruitment delivery fees and periods (reference data: settings.manage).
 */
class UpdateRecruitmentSettingsRequest extends FormRequest
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

        foreach (Setting::RECRUITMENT as $key => $definition) {
            $rules[$key] = ['required', 'integer', 'min:'.$definition['min'], 'max:'.$definition['max']];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return collect(Setting::RECRUITMENT)->mapWithKeys(fn (array $definition, string $key): array => [$key => __("settings.recruitment.fields.{$key}.label")])->all();
    }
}
