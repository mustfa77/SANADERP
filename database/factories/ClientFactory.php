<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * [Arabic, English] Saudi names.
     *
     * @var list<array{0: string, 1: string}>
     */
    private const NAMES = [
        ['عبدالله بن سعد القحطاني', 'Abdullah Al-Qahtani'],
        ['نورة محمد العتيبي', 'Noura Al-Otaibi'],
        ['فهد عبدالرحمن الدوسري', 'Fahad Al-Dosari'],
        ['سارة خالد الشهري', 'Sarah Al-Shehri'],
        ['محمد ناصر الحربي', 'Mohammed Al-Harbi'],
        ['ريم عبدالعزيز المطيري', 'Reem Al-Mutairi'],
        ['خالد إبراهيم الغامدي', 'Khalid Al-Ghamdi'],
        ['هيا سلمان الزهراني', 'Haya Al-Zahrani'],
        ['تركي فيصل العنزي', 'Turki Al-Anazi'],
        ['منيرة سعود الشمري', 'Munira Al-Shammari'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$nameAr, $nameEn] = fake()->randomElement(self::NAMES);

        return [
            'name_ar' => $nameAr,
            'name_en' => $nameEn,
            'national_id' => '1'.fake()->unique()->numerify('#########'),
            'phone' => '05'.fake()->numerify('########'),
            'email' => fake()->optional()->safeEmail(),
            'city' => fake()->randomElement(['الرياض', 'بريدة', 'عنيزة', 'الخرج']),
            'district' => fake()->randomElement(['النرجس', 'الملقا', 'الصفراء', 'الريان']),
            'national_address' => strtoupper(fake()->bothify('????####')),
            'branch_id' => Branch::query()->inRandomOrder()->value('id') ?? Branch::factory(),
            'is_active' => true,
        ];
    }
}
