<?php

namespace Database\Seeders;

use App\Enums\LookupType;
use App\Enums\RentalType;
use App\Enums\SponsorType;
use App\Enums\WorkerRentalType;
use App\Enums\WorkerStatus;
use App\Models\Branch;
use App\Models\Camp;
use App\Models\Client;
use App\Models\Country;
use App\Models\HourlyBooking;
use App\Models\Lookup;
use App\Models\Price;
use App\Models\RentalContract;
use App\Models\Shift;
use App\Models\Worker;
use App\Services\Rental\HourlyBookingService;
use App\Services\Rental\PaymentService;
use App\Services\Rental\RentalContractService;
use App\Services\Workers\WorkerStatusService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Rental sample data for development ONLY (refuses to run outside local /
 * testing). Not called by DatabaseSeeder:
 *
 *   php artisan db:seed --class=RentalDemoSeeder
 *
 * Per active branch: a housing, monthly and hourly workers (hourly ones from
 * the Philippines), workers not in rental yet, clients, monthly contracts
 * (active, ending soon, renewed, terminated), hourly bookings (past
 * completed, upcoming, cancelled) and payments (paid, partial, unpaid,
 * verified). Everything goes through the real services.
 */
class RentalDemoSeeder extends Seeder
{
    /**
     * Monthly price (before VAT) by country code; others use DEFAULT_MONTHLY.
     */
    private const MONTHLY_PRICES = ['PH' => 2600, 'ID' => 2200, 'LK' => 2000, 'KE' => 1800, 'UG' => 1700, 'ET' => 1600, 'IN' => 1900, 'BD' => 1700];

    private const DEFAULT_MONTHLY = 1900;

    private const HOURLY_PRICE_PH = 45;

    public function run(
        WorkerStatusService $statuses,
        RentalContractService $contracts,
        HourlyBookingService $bookings,
        PaymentService $payments,
    ): void {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('RentalDemoSeeder must never run in production.');
        }

        $this->call([BranchSeeder::class, CountrySeeder::class, ProfessionSeeder::class, LookupSeeder::class]);

        if (RentalContract::query()->withoutGlobalScopes()->exists() || HourlyBooking::query()->withoutGlobalScopes()->exists()) {
            $this->command?->warn('Rental data already exists: nothing was added.');

            return;
        }

        $shifts = $this->shifts();
        $this->prices();
        $philippines = Country::query()->where('code', 'PH')->firstOrFail();
        $monthlyCountries = Country::query()->active()->where('id', '!=', $philippines->id)->get();
        $methods = Lookup::query()->ofType(LookupType::PaymentMethod)->active()->ordered()->get();
        $cancelReason = Lookup::query()->ofType(LookupType::CancelReason)->active()->first();
        $returnReason = Lookup::query()->ofType(LookupType::ReturnReason)->active()->first();

        foreach (Branch::query()->where('is_active', true)->orderBy('id')->get() as $branchIndex => $branch) {
            $camp = $this->camp($branch);
            $clients = Client::factory()->count(5)->create(['branch_id' => $branch->id]);

            $worker = function (WorkerRentalType $type, Country $country) use ($branch, $camp, $statuses): Worker {
                $worker = Worker::factory()->withIqama()->create([
                    'branch_id' => $branch->id,
                    'country_id' => $country->id,
                    'sponsor_type' => SponsorType::Company,
                    'rental_type' => $type,
                    'camp_id' => $camp->id,
                ]);

                return $statuses->initialize($worker, WorkerStatus::Available, 'Demo data');
            };

            $monthly = collect(range(0, 5))->map(fn (int $i) => $worker(WorkerRentalType::Monthly, $monthlyCountries[($i + $branchIndex) % $monthlyCountries->count()]));
            $hourly = collect(range(0, 3))->map(fn () => $worker(WorkerRentalType::Hourly, $philippines));
            collect(range(0, 2))->each(fn (int $i) => $worker(WorkerRentalType::None, $i === 0 ? $philippines : $monthlyCountries[$i % $monthlyCountries->count()]));

            // Monthly contracts: active, ending soon, renewed, terminated (the last two monthly workers stay free).
            $active = $this->asOf(today()->subDays(10), fn () => $contracts->create(['client_id' => $clients[0]->id, 'worker_id' => $monthly[0]->id, 'starts_at' => today()->toDateString()]));
            $payments->record($active, $this->payment($methods, $active->total, today()->subDays(10)));

            $endingSoon = $this->asOf(today()->subDays(27), fn () => $contracts->create(['client_id' => $clients[1]->id, 'worker_id' => $monthly[1]->id, 'starts_at' => today()->toDateString(), 'discount' => 100]));
            $payments->record($endingSoon, $this->payment($methods, 1000, today()->subDays(27)));

            $first = $this->asOf(today()->subDays(45), fn () => $contracts->create(['client_id' => $clients[2]->id, 'worker_id' => $monthly[2]->id, 'starts_at' => today()->toDateString()]));
            $payments->verify($payments->record($first, $this->payment($methods, $first->total, today()->subDays(44))));
            $renewal = $contracts->renew($first);
            $payments->record($renewal, $this->payment($methods, $renewal->total, $renewal->starts_at));

            $terminated = $this->asOf(today()->subDays(20), fn () => $contracts->create(['client_id' => $clients[3]->id, 'worker_id' => $monthly[3]->id, 'starts_at' => today()->toDateString()]));
            $payments->record($terminated, $this->payment($methods, $terminated->total, today()->subDays(20)));
            $contracts->close($terminated, today()->subDays(5)->toDateString(), $returnReason, 'Demo: client travelled', $camp);

            // Hourly bookings: past (completed and paid), upcoming, one cancelled.
            foreach ($hourly as $index => $filipina) {
                $shift = $shifts[$index % $shifts->count()];
                $client = $clients[$index % $clients->count()];

                $past = $this->asOf(today()->subDays(6)->setTime(6, 0), fn () => $bookings->book([
                    'client_id' => $client->id, 'worker_id' => $filipina->id, 'shift_id' => $shift->id,
                    'date' => today()->toDateString(), 'days' => 3, 'address' => 'Demo address',
                ]));

                foreach ($past as $booking) {
                    $bookings->complete($booking);
                    $payments->record($booking, $this->payment($methods, $booking->total, $booking->date));
                }

                $upcoming = $bookings->book([
                    'client_id' => $clients[($index + 1) % $clients->count()]->id, 'worker_id' => $filipina->id, 'shift_id' => $shift->id,
                    'date' => today()->addDays($index)->toDateString(), 'days' => 2 + $index,
                ]);

                if ($index === 0) {
                    $payments->record($upcoming->first(), $this->payment($methods, 50, today()));
                }

                if ($index === 1 && $cancelReason) {
                    $bookings->cancel($upcoming->last(), $cancelReason, 'Demo: client cancelled');
                }
            }
        }

        $this->command?->info('Rental demo data added.');
    }

    /**
     * @return Collection<int, Shift>
     */
    private function shifts()
    {
        if (Shift::query()->active()->exists()) {
            return Shift::query()->active()->ordered()->get();
        }

        foreach ([
            ['name_ar' => 'صباحية', 'name_en' => 'Morning', 'start_time' => '08:00:00', 'move_time' => '07:15:00', 'hours' => 4],
            ['name_ar' => 'مسائية', 'name_en' => 'Evening', 'start_time' => '16:00:00', 'move_time' => '15:15:00', 'hours' => 4],
            ['name_ar' => 'يوم كامل', 'name_en' => 'Full day', 'start_time' => '08:00:00', 'move_time' => '07:15:00', 'hours' => 8],
        ] as $index => $shift) {
            Shift::query()->create([...$shift, 'sort_order' => $index + 1, 'is_active' => true]);
        }

        return Shift::query()->active()->ordered()->get();
    }

    /**
     * Monthly price for every active country, hourly price for the
     * Philippines only (existing active prices are kept).
     */
    private function prices(): void
    {
        foreach (Country::query()->active()->get() as $country) {
            if (! Price::query()->active()->where('country_id', $country->id)->where('rental_type', RentalType::Monthly)->exists()) {
                Price::query()->create([
                    'country_id' => $country->id,
                    'rental_type' => RentalType::Monthly,
                    'price' => self::MONTHLY_PRICES[$country->code] ?? self::DEFAULT_MONTHLY,
                    'vat_percent' => 15,
                    'is_active' => true,
                ]);
            }
        }

        $philippines = Country::query()->where('code', 'PH')->firstOrFail();

        if (! Price::query()->active()->where('country_id', $philippines->id)->where('rental_type', RentalType::Hourly)->exists()) {
            Price::query()->create(['country_id' => $philippines->id, 'rental_type' => RentalType::Hourly, 'price' => self::HOURLY_PRICE_PH, 'vat_percent' => 15, 'is_active' => true]);
        }
    }

    private function camp(Branch $branch): Camp
    {
        return Camp::query()->withoutGlobalScopes()->where('branch_id', $branch->id)->where('is_active', true)->first()
            ?? Camp::factory()->create(['branch_id' => $branch->id, 'capacity' => 60]);
    }

    /**
     * @param  Collection<int, Lookup>  $methods
     * @return array{amount: float, paid_at: string, payment_method_id: int, reference: string}
     */
    private function payment($methods, float|string $amount, Carbon $date): array
    {
        return [
            'amount' => (float) $amount,
            'paid_at' => min($date, today())->toDateString(),
            'payment_method_id' => $methods->random()->id,
            'reference' => 'DEMO-'.random_int(10000, 99999),
        ];
    }

    /**
     * Run $callback as if today were $date (to create past contracts and
     * bookings through the services, which refuse past dates).
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function asOf(Carbon $date, callable $callback): mixed
    {
        Carbon::setTestNow($date);

        try {
            return $callback();
        } finally {
            Carbon::setTestNow();
        }
    }
}
