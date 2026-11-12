<?php

namespace App\Http\Requests\Admin;

use App\Services\SiteSettings;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Site name and logos. SVG is not accepted (it can carry scripts).
 */
class UpdateGeneralSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('settings.edit');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $rules = [
            'site_name_ar' => ['required', 'string', 'max:100'],
            'site_name_en' => ['required', 'string', 'max:100'],
        ];

        foreach (array_keys(SiteSettings::IMAGES) as $key) {
            $rules[$key] = $key === 'favicon'
                ? ['nullable', 'file', 'mimes:ico,png', 'max:512']
                : ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'];
            $rules['remove_'.$key] = ['nullable', 'boolean'];
        }

        return $rules;
    }
}
