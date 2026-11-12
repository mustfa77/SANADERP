<?php

namespace Database\Seeders;

use App\Models\Profession;
use Illuminate\Database\Seeder;

class ProfessionSeeder extends Seeder
{
    /**
     * Default professions (only added when missing).
     */
    public function run(): void
    {
        $professions = [
            ['name_ar' => 'عاملة منزلية', 'name_en' => 'Housemaid'],
            ['name_ar' => 'سائق خاص', 'name_en' => 'Private driver'],
            ['name_ar' => 'مربية أطفال', 'name_en' => 'Nanny'],
        ];

        foreach ($professions as $profession) {
            Profession::query()->firstOrCreate(['name_en' => $profession['name_en']], $profession);
        }
    }
}
