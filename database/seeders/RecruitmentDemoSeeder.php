<?php

namespace Database\Seeders;

use App\Enums\DeliveryMethod;
use App\Enums\Gender;
use App\Enums\LookupType;
use App\Enums\SponsorType;
use App\Enums\WorkerStatus;
use App\Models\Agency;
use App\Models\Branch;
use App\Models\Camp;
use App\Models\Client;
use App\Models\Country;
use App\Models\Lookup;
use App\Models\Profession;
use App\Models\RecruitmentOrder;
use App\Models\Setting;
use App\Models\User;
use App\Models\Worker;
use App\Services\Recruitment\RecruitmentService;
use App\Services\Workers\WorkerStatusService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * Recruitment sample data for development ONLY (refuses to run outside local
 * / testing). Not called by DatabaseSeeder:
 *
 *   php artisan db:seed --class=RecruitmentDemoSeeder
 *
 * Per active branch, one order at every step of the flow, all through
 * RecruitmentService (so worker statuses, history and audit look real):
 * new, candidate selected, contracted, in travel (on time and overdue),
 * arrived (today and waiting with camp fees), delivered (office / home,
 * probation ending soon), completed, returned and cancelled.
 */
class RecruitmentDemoSeeder extends Seeder
{
    public const MARKER = 'Demo data (RecruitmentDemoSeeder)';

    /** Days between two travel stages (every stage allows at least 3 days). */
    private const STAGE_GAP = 3;

    private int $visa = 0;

    public function __construct(private RecruitmentService $recruitment, private WorkerStatusService $statuses) {}

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('RecruitmentDemoSeeder must never run in production.');
        }

        $this->call([BranchSeeder::class, CountrySeeder::class, ProfessionSeeder::class, LookupSeeder::class]);

        if (RecruitmentOrder::query()->withoutGlobalScopes()->where('notes', self::MARKER)->exists()) {
            $this->command?->warn('Recruitment demo data already exists: nothing was added.');

            return;
        }

        // Names in the "created by" / "delivered by" columns.
        if ($admin = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'super_admin'))->first()) {
            Auth::setUser($admin);
        }

        $this->visa = (int) (now()->format('ymd').'0000');
        $countries = Country::query()->active()->has('travelStages')->get();
        $professions = Profession::query()->active()->get();
        $cancelReason = Lookup::query()->ofType(LookupType::CancelReason)->active()->firstOrFail();
        $returnReason = Lookup::query()->ofType(LookupType::ReturnReason)->active()->firstOrFail();
        $probation = Setting::integer('probation_days');
        $this->agencies($countries);

        foreach (Branch::query()->where('is_active', true)->orderBy('id')->get() as $branchIndex => $branch) {
            $camp = Camp::query()->withoutGlobalScopes()->where('branch_id', $branch->id)->where('is_active', true)->first()
                ?? Camp::factory()->create(['branch_id' => $branch->id, 'capacity' => 60]);
            $clients = Client::factory()->count(6)->create(['branch_id' => $branch->id]);

            $scenarios = [
                ['step' => 'new'],
                ['step' => 'selected', 'start' => 2],
                ['step' => 'contracted', 'start' => 8],
                ['step' => 'travel', 'travel' => 9, 'stages' => 2],
                ['step' => 'travel', 'travel' => 25, 'stages' => 0],  // first stage overdue
                ['step' => 'arrived', 'arrived' => 0],
                ['step' => 'arrived', 'arrived' => 6],                // waiting, camp fees
                ['step' => 'delivered', 'delivered' => 15, 'camp' => 2, 'method' => DeliveryMethod::Office],
                ['step' => 'delivered', 'delivered' => max(1, $probation - 2), 'camp' => 4, 'method' => DeliveryMethod::Home], // probation ends soon
                ['step' => 'completed', 'delivered' => $probation + 10, 'camp' => 1, 'method' => DeliveryMethod::Office],
                ['step' => 'returned', 'delivered' => 20, 'camp' => 1, 'method' => DeliveryMethod::Office],
                ['step' => 'cancelled', 'start' => 4],
            ];

            foreach ($scenarios as $index => $scenario) {
                $country = $countries[($index + $branchIndex) % $countries->count()];
                $profession = $professions[$index % $professions->count()];

                $this->order($scenario, $branch, $clients[$index % $clients->count()], $country, $profession, $camp, $cancelReason, $returnReason, $probation);
            }
        }

        Auth::forgetUser();
        $this->command?->info('Recruitment demo data added.');
    }

    /**
     * Build one order up to its step. Dates are counted back from today so
     * that the order reaches its step "now".
     *
     * @param  array<string, mixed>  $scenario
     */
    private function order(array $scenario, Branch $branch, Client $client, Country $country, Profession $profession, Camp $camp, Lookup $cancelReason, Lookup $returnReason, int $probation): void
    {
        $stages = $country->travelStages()->active()->count();
        $step = $scenario['step'];

        // Arrival day (days ago) for the steps that reach arrival.
        $arrivedAgo = match ($step) {
            'arrived' => $scenario['arrived'],
            'delivered', 'completed', 'returned' => $scenario['delivered'] + $scenario['camp'],
            default => null,
        };

        $travelAgo = $arrivedAgo !== null ? $arrivedAgo + $stages * self::STAGE_GAP : ($scenario['travel'] ?? null);
        $startAgo = $travelAgo !== null ? $travelAgo + 6 : $scenario['start'] ?? 0;
        $start = today()->subDays($startAgo);

        $order = $this->asOf($start->copy()->setTime(10, 0), fn () => $this->recruitment->create([
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'country_id' => $country->id,
            'profession_id' => $profession->id,
            'contract_amount' => [12500, 13500, 14500, 16000][$startAgo % 4],
            'notes' => self::MARKER,
        ]));

        if ($step === 'new') {
            return;
        }

        $worker = $this->candidate($branch, $country, $profession);
        $order = $this->recruitment->selectCandidate($order, $worker);

        if ($step === 'cancelled') {
            $this->recruitment->cancel($order, $cancelReason, 'Demo: client changed mind');

            return;
        }

        if ($step === 'selected') {
            return;
        }

        $order = $this->recruitment->contract($order, [
            'visa_number' => (string) ++$this->visa,
            'visa_issued_at' => $start->copy()->addDay()->toDateString(),
            'musaned_contract_no' => 'MSN-'.random_int(100000, 999999),
            'contracted_at' => $start->copy()->addDays($travelAgo !== null ? 3 : 1)->min(today())->toDateString(),
        ]);

        if ($step === 'contracted') {
            return;
        }

        $travel = today()->subDays($travelAgo);
        $order = $this->recruitment->startTravel($order, $travel->toDateString());
        $done = $step === 'travel' ? $scenario['stages'] : $stages;

        foreach ($order->stages()->get()->take($done)->values() as $i => $stage) {
            $order = $this->recruitment->completeStage($order, $stage, $travel->copy()->addDays(($i + 1) * self::STAGE_GAP)->min(today())->toDateString());
        }

        if ($step === 'travel') {
            return;
        }

        $arrived = today()->subDays($arrivedAgo);
        $order = $this->recruitment->markArrived($order, $arrived->toDateString(), $camp);

        if ($step === 'arrived') {
            return;
        }

        $delivered = today()->subDays($scenario['delivered']);
        $order = $this->recruitment->deliver($order, [
            'delivered_at' => $delivered->toDateString(),
            'delivery_method' => $scenario['method']->value,
            'receiver_name' => $client->trans('name'),
            'receiver_phone' => $client->phone,
            'delivery_notes' => 'Demo delivery',
        ]);

        match ($step) {
            'completed' => $this->recruitment->completeProbation($order, $delivered->copy()->addDays($probation)->min(today())->toDateString()),
            'returned' => $this->recruitment->returnDuringProbation($order, $delivered->copy()->addDays(7)->toDateString(), $returnReason, note: 'Demo: client not satisfied'),
            default => null,
        };
    }

    /**
     * A screened candidate (client sponsorship, ready for selection).
     */
    private function candidate(Branch $branch, Country $country, Profession $profession): Worker
    {
        $male = in_array($profession->cv_template, ['driver', 'house_boy'], true);

        $worker = Worker::factory()->create([
            'branch_id' => $branch->id,
            'country_id' => $country->id,
            'profession_id' => $profession->id,
            'sponsor_type' => SponsorType::Client,
            'agency_id' => Agency::query()->where('country_id', $country->id)->value('id'),
            ...($male ? ['gender' => Gender::Male, 'name_en' => fake()->name('male'), 'name_ar' => fake('ar_SA')->name('male')] : []),
        ]);
        $worker->forceFill(['cv_reference_no' => $country->code.'-'.str_pad((string) $worker->id, 3, '0', STR_PAD_LEFT)])->save();

        $this->statuses->transition($worker, WorkerStatus::UnderReview, 'Demo data');
        $this->statuses->transition($worker, WorkerStatus::ReadyForSelection, 'Demo data');

        return $worker;
    }

    /**
     * One external agency per country (CV and agency portal use it).
     *
     * @param  Collection<int, Country>  $countries
     */
    private function agencies(Collection $countries): void
    {
        foreach ($countries as $country) {
            if (! Agency::query()->where('country_id', $country->id)->exists()) {
                Agency::factory()->create(['country_id' => $country->id, 'country' => $country->code, 'name_en' => $country->name_en.' Manpower']);
            }
        }
    }

    /**
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
