<?php

namespace App\Http\Requests\Admin;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSecuritySettingsRequest extends FormRequest
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
        $rules = [];

        foreach (Setting::SECURITY as $key => $definition) {
            $rules[$key] = ['required', 'integer', 'min:'.$definition['min'], 'max:'.$definition['max']];
        }

        return $rules;
    }
}
