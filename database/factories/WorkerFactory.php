<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\PassportType;
use App\Enums\Religion;
use App\Enums\SponsorType;
use App\Enums\WorkerRentalType;
use App\Models\Branch;
use App\Models\Country;
use App\Models\Profession;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Workers start as candidate_new (status is set by WorkerStatusService).
 *
 * @extends Factory<Worker>
 */
class WorkerFactory extends Factory
{
    /**
     * Realistic names per nationality code: [English, Arabic].
     *
     * @var array<string, list<array{0: string, 1: string}>>
     */
    private const NAMES = [
        'PH' => [['Maria Santos', 'ماريا سانتوس'], ['Grace Reyes', 'غريس رييس'], ['Joy Dela Cruz', 'جوي ديلا كروز'], ['Rosalie Garcia', 'روزالي غارسيا']],
        'ID' => [['Siti Rahayu', 'سيتي رحايو'], ['Dewi Lestari', 'ديوي ليستاري'], ['Nur Aini', 'نور عيني'], ['Sri Wahyuni', 'سري واهيوني']],
        'KE' => [['Grace Wanjiru', 'غريس وانجيرو'], ['Mercy Achieng', 'ميرسي أتشينغ'], ['Faith Njeri', 'فيث نجيري']],
        'UG' => [['Nakato Sarah', 'ناكاتو سارة'], ['Namubiru Ruth', 'ناموبيرو روث'], ['Atim Esther', 'أتيم إستر']],
        'ET' => [['Aster Bekele', 'أستر بقلي'], ['Tigist Haile', 'تيجست هايلي'], ['Meron Tesfaye', 'ميرون تسفاي']],
        'LK' => [['Dilani Perera', 'ديلاني بيريرا'], ['Kumari Silva', 'كوماري سيلفا'], ['Nadeesha Fernando', 'ناديشا فرناندو']],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $country = Country::query()->inRandomOrder()->first() ?? Country::factory()->create();
        [$nameEn, $nameAr] = fake()->randomElement(self::NAMES[$country->code] ?? [[fake()->name('female'), fake('ar_SA')->name('female')]]);

        return [
            'name_en' => $nameEn,
            'name_ar' => $nameAr,
            'gender' => Gender::Female,
            'birth_date' => fake()->dateTimeBetween('-45 years', '-22 years'),
            'religion' => fake()->randomElement(Religion::cases()),
            'marital_status' => fake()->randomElement(MaritalStatus::cases()),
            'country_id' => $country->id,
            'profession_id' => Profession::query()->inRandomOrder()->value('id') ?? Profession::factory(),
            'agency_id' => null,
            'branch_id' => Branch::query()->inRandomOrder()->value('id') ?? Branch::factory(),
            'passport_no' => strtoupper(fake()->unique()->bothify('?#######')),
            'passport_type' => PassportType::Ordinary,
            'passport_expiry' => fake()->dateTimeBetween('+6 months', '+8 years'),
            'experience_years' => fake()->numberBetween(0, 12),
            'salary' => fake()->randomElement([1200, 1300, 1500, 1800, 2000]),
            'sponsor_type' => fake()->randomElement(SponsorType::cases()),
            'rental_type' => WorkerRentalType::None,
        ];
    }

    public function company(): static
    {
        return $this->state(['sponsor_type' => SponsorType::Company]);
    }

    public function client(): static
    {
        return $this->state(['sponsor_type' => SponsorType::Client]);
    }

    public function withIqama(): static
    {
        return $this->state(fn (): array => [
            'iqama_no' => '2'.fake()->unique()->numerify('#########'),
            'iqama_expiry' => fake()->dateTimeBetween('+1 month', '+2 years'),
            'medical_expiry' => fake()->dateTimeBetween('+1 month', '+1 year'),
        ]);
    }
}
