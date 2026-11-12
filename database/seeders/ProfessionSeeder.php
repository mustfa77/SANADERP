<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use App\Models\Profession;
use Illuminate\Database\Seeder;

class ProfessionSeeder extends Seeder
{
    /**
     * Default professions with their CV template (config/cv_templates.php),
     * and the documents specific to drivers. Only added when missing.
     */
    public function run(): void
    {
        $professions = [
            ['name_ar' => 'عاملة منزلية', 'name_en' => 'Housemaid', 'cv_template' => 'housemaid'],
            ['name_ar' => 'سائق خاص', 'name_en' => 'Private driver', 'cv_template' => 'driver'],
            ['name_ar' => 'عامل منزلي', 'name_en' => 'House boy', 'cv_template' => 'house_boy'],
            ['name_ar' => 'مقدم رعاية', 'name_en' => 'Care giver', 'cv_template' => 'care_giver'],
            // No CV sample yet: uses the housemaid template until one is defined.
            ['name_ar' => 'مربية أطفال', 'name_en' => 'Nanny', 'cv_template' => 'housemaid'],
        ];

        foreach ($professions as $profession) {
            Profession::query()->firstOrCreate(['name_en' => $profession['name_en']], $profession);
        }

        $driver = Profession::query()->where('name_en', 'Private driver')->first();

        if ($driver) {
            $documents = [
                ['name_ar' => 'صورة رخصة القيادة', 'name_en' => 'Driving licence copy', 'is_required' => true],
                ['name_ar' => 'شهادة الاختبار المهني', 'name_en' => 'Professional test certificate', 'is_required' => false],
            ];

            foreach ($documents as $document) {
                DocumentType::query()->firstOrCreate(
                    ['applies_to' => 'worker', 'profession_id' => $driver->id, 'country_id' => null, 'name_en' => $document['name_en']],
                    [...$document, 'include_in_authority_file' => true],
                );
            }
        }
    }
}
