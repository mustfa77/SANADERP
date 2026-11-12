<?php

namespace Database\Seeders;

use App\Enums\LookupType;
use App\Models\Lookup;
use Illuminate\Database\Seeder;

class LookupSeeder extends Seeder
{
    /**
     * Default lookup items per type (only added when missing).
     */
    public function run(): void
    {
        $items = [
            LookupType::ReturnReason->value => [
                ['عدم رغبة العاملة في العمل', 'Worker unwilling to work'],
                ['عدم الكفاءة', 'Lack of competence'],
                ['أسباب صحية', 'Health reasons'],
                ['عدم رضا العميل', 'Client not satisfied'],
            ],
            LookupType::CancelReason->value => [
                ['طلب العميل', 'Client request'],
                ['تأخر الإجراءات', 'Procedure delay'],
                ['رفض التأشيرة', 'Visa rejected'],
                ['عدم توفر عاملة', 'No worker available'],
            ],
            LookupType::Language->value => [
                ['العربية', 'Arabic'],
                ['الإنجليزية', 'English'],
                ['التاغالوغية', 'Tagalog'],
                ['الإندونيسية', 'Indonesian'],
                ['السواحيلية', 'Swahili'],
                ['الأمهرية', 'Amharic'],
            ],
            LookupType::Skill->value => [
                ['الطبخ', 'Cooking'],
                ['التنظيف', 'Cleaning'],
                ['رعاية الأطفال', 'Childcare'],
                ['رعاية كبار السن', 'Elderly care'],
                ['الكي والغسيل', 'Laundry & ironing'],
                ['القيادة', 'Driving'],
            ],
            LookupType::PaymentMethod->value => [
                ['نقداً', 'Cash'],
                ['مدى', 'Mada'],
                ['تحويل بنكي', 'Bank transfer'],
                ['سداد', 'SADAD'],
            ],
        ];

        foreach ($items as $type => $names) {
            foreach ($names as [$nameAr, $nameEn]) {
                Lookup::query()->firstOrCreate(['type' => $type, 'name_en' => $nameEn], ['name_ar' => $nameAr]);
            }
        }
    }
}
