<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Example travel stages (name_ar, name_en, max_days), editable per
     * country from Settings > Countries.
     *
     * @var list<array{0: string, 1: string, 2: int}>
     */
    private const EXAMPLE_STAGES = [
        ['الفحص الطبي', 'Medical examination', 7],
        ['توقيع العقد', 'Contract signing', 5],
        ['إصدار التأشيرة', 'Visa issuance', 14],
        ['التصديق من السفارة', 'Embassy stamping', 10],
        ['تصريح السفر', 'Travel clearance', 7],
        ['حجز التذكرة', 'Ticket booking', 5],
        ['الوصول', 'Arrival', 3],
    ];

    /**
     * Seed recruitment countries (only on first create) and map the legacy
     * 2-letter "country" of existing agencies to country_id.
     */
    public function run(): void
    {
        $countries = [
            ['code' => 'PH', 'name_ar' => 'الفلبين', 'name_en' => 'Philippines', 'nationality_ar' => 'فلبينية', 'nationality_en' => 'Filipino'],
            ['code' => 'ID', 'name_ar' => 'إندونيسيا', 'name_en' => 'Indonesia', 'nationality_ar' => 'إندونيسية', 'nationality_en' => 'Indonesian'],
            ['code' => 'KE', 'name_ar' => 'كينيا', 'name_en' => 'Kenya', 'nationality_ar' => 'كينية', 'nationality_en' => 'Kenyan'],
            ['code' => 'UG', 'name_ar' => 'أوغندا', 'name_en' => 'Uganda', 'nationality_ar' => 'أوغندية', 'nationality_en' => 'Ugandan'],
            ['code' => 'ET', 'name_ar' => 'إثيوبيا', 'name_en' => 'Ethiopia', 'nationality_ar' => 'إثيوبية', 'nationality_en' => 'Ethiopian'],
            ['code' => 'LK', 'name_ar' => 'سريلانكا', 'name_en' => 'Sri Lanka', 'nationality_ar' => 'سريلانكية', 'nationality_en' => 'Sri Lankan'],
        ];

        foreach ($countries as $data) {
            $country = Country::query()->firstOrCreate(['code' => $data['code']], $data);

            if ($country->wasRecentlyCreated) {
                foreach (self::EXAMPLE_STAGES as [$nameAr, $nameEn, $maxDays]) {
                    $country->travelStages()->create(['name_ar' => $nameAr, 'name_en' => $nameEn, 'max_days' => $maxDays]);
                }
            }
        }

        Agency::query()
            ->whereNull('country_id')
            ->whereNotNull('country')
            ->each(function (Agency $agency): void {
                $countryId = Country::query()->where('code', strtoupper($agency->country))->value('id');

                if ($countryId) {
                    $agency->forceFill(['country_id' => $countryId])->save();
                }
            });
    }
}
