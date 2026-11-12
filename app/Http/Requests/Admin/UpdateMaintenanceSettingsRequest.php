<?php

namespace App\Http\Requests\Admin;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Maintenance mode, automatic backup and log retention.
 */
class UpdateMaintenanceSettingsRequest extends FormRequest
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
            'maintenance_enabled' => ['nullable', 'boolean'],
            'maintenance_message_ar' => ['nullable', 'string', 'max:1000'],
            'maintenance_message_en' => ['nullable', 'string', 'max:1000'],
            'backup_auto_enabled' => ['nullable', 'boolean'],
        ];

        foreach (Setting::MAINTENANCE as $key => $definition) {
            $rules[$key] = ['required', 'integer', 'min:'.$definition['min'], 'max:'.$definition['max']];
        }

        return $rules;
    }
}
