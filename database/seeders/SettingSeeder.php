<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Seed default settings without overwriting values changed later.
     */
    public function run(): void
    {
        foreach (Setting::SECURITY + Setting::MAINTENANCE as $key => $definition) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => (string) $definition['default']]);
        }
    }
}
