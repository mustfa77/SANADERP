<?php

namespace App\Http\Requests\Admin;

use App\Services\SiteSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Panel appearance. The color is a strict #rrggbb value because it is
 * written into an inline <style>.
 */
class UpdateAppearanceSettingsRequest extends FormRequest
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
        $rules = ['primary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/']];

        foreach (SiteSettings::APPEARANCE as $key => $definition) {
            $rules[$key] = ['required', Rule::in($definition['options'])];
        }

        return $rules;
    }
}
