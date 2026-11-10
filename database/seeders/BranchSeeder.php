<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Seed the company's current branches (details are only filled on create).
     */
    public function run(): void
    {
        $branches = [
            ['code' => 'RYD', 'name_ar' => 'الرياض', 'name_en' => 'Riyadh', 'city_ar' => 'الرياض', 'city_en' => 'Riyadh'],
            ['code' => 'QSM', 'name_ar' => 'القصيم', 'name_en' => 'Qassim', 'city_ar' => 'بريدة', 'city_en' => 'Buraydah'],
        ];

        foreach ($branches as $branch) {
            Branch::query()->firstOrCreate(['code' => $branch['code']], $branch);
        }
    }
}
