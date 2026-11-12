<?php

namespace Tests\Feature\Services\Rental;

use App\Enums\LookupType;
use App\Enums\RefundStatus;
use App\Enums\RentalContractStatus;
use App\Enums\SponsorType;
use App\Enums\WorkerRentalType;
use App\Enums\WorkerStatus;
use App\Models\Branch;
use App\Models\Camp;
use App\Models\Client;
use App\Models\Country;
use App\Models\Lookup;
use App\Models\Price;
use App\Models\RentalContract;
use App\Models\Worker;
use App\Services\Rental\PaymentService;
use App\Services\Rental\RefundService;
use App\Services\Rental\RentalContractService;
use App\Services\Rental\RentalException;
use App\Services\Workers\WorkerStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Tests\TestCase;

class RentalContractServiceTest extends TestCase
{
    use RefreshDatabase;

    private RentalContractService $service;

    private Client $client;

    private Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RentalContractService::class);
        $this->client = Client::factory()->create();
        $this->country = Country::factory()->create(['code' => 'ID']);
        Price::factory()->create(['country_id' => $this->country->id, 'price' => 2000, 'vat_percent' => 15]);
    }

    private function worker(array $attributes = []): Worker
    {
        $worker = Worker::factory()->create([
            'branch_id' => $this->client->branch_id,
            'country_id' => $this->country->id,
            'sponsor_type' => SponsorType::Company,
            'rental_type' => WorkerRentalType::Monthly,
            ...$attributes,
        ]);

        return app(WorkerStatusService::class)->initialize($worker, WorkerStatus::Available);
    }

    private function contract(?Worker $worker = null, string $start = '2026-11-01', array $data = []): RentalContract
    {
        return $this->service->create([
            'client_id' => $this->client->id,
            'worker_id' => ($worker ?? $this->worker())->id,
            'starts_at' => $start,
            ...$data,
        ]);
    }

    public function test_a_contract_is_one_month_priced_from_the_country_with_vat(): void
    {
        $camp = Camp::factory()->create(['branch_id' => $this->client->branch_id]);
        $worker = $this->worker();
        $worker->forceFill(['camp_id' => $camp->id])->save();

        $contract = $this->contract($worker, '2026-11-01', ['discount' => 100]);

        $this->assertSame('MR-'.now()->format('Y').'-0001', $contract->number);
        $this->assertSame('2026-11-30', $contract->ends_at->toDateString());
        $this->assertSame('2000.00', $contract->price);
        // (2000 - 100) × 15% = 285
        $this->assertSame('285.00', $contract->vat_amount);
        $this->assertSame('2185.00', $contract->total);
        $this->assertSame(WorkerStatus::RentedMonthly, $worker->fresh()->status);
        $this->assertNull($worker->fresh()->camp_id);

        // Month ends are handled (31 Jan -> 27 Feb).
        $this->assertSame('2027-02-27', $this->service->endDate(now()->setDate(2027, 1, 31))->toDateString());
    }

    public function test_only_available_monthly_company_workers_of_the_clients_branch_can_be_rented(): void
    {
        $cases = [
            $this->worker(['rental_type' => WorkerRentalType::Hourly]),
            $this->worker(['rental_type' => WorkerRentalType::None]),
            $this->worker(['branch_id' => Client::factory()->create(['branch_id' => Branch::factory()->create()->id])->branch_id]),
        ];
        $rented = $this->worker();
        $this->contract($rented);
        $cases[] = $rented;

        foreach ($cases as $worker) {
            try {
                $this->contract($worker);
                $this->fail('Expected RentalException');
            } catch (RentalException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_a_price_is_needed(): void
    {
        $worker = $this->worker(['country_id' => Country::factory()->create()->id]);

        try {
            $this->contract($worker);
            $this->fail('Expected RentalException');
        } catch (RentalException) {
            $this->assertSame(WorkerStatus::Available, $worker->fresh()->status);
        }

        $this->assertSame('1500.00', $this->contract($worker, data: ['price' => 1500])->price);
    }

    public function test_renewal_creates_the_next_months_contract(): void
    {
        $contract = $this->contract();

        $renewal = $this->service->renew($contract, ['price' => 2100]);

        $this->assertSame(RentalContractStatus::Renewed, $contract->fresh()->status);
        $this->assertSame(RentalContractStatus::Active, $renewal->status);
        $this->assertSame('2026-12-01', $renewal->starts_at->toDateString());
        $this->assertSame('2026-12-31', $renewal->ends_at->toDateString());
        $this->assertTrue($renewal->renewedFrom->is($contract));
        $this->assertSame('2415.00', $renewal->total);
        $this->assertSame(WorkerStatus::RentedMonthly, $contract->worker->fresh()->status);

        // A renewed contract cannot be renewed again.
        $this->expectException(RentalException::class);
        $this->service->renew($contract->fresh());
    }

    public function test_closing_returns_the_worker_and_early_termination_needs_a_reason(): void
    {
        $camp = Camp::factory()->create(['branch_id' => $this->client->branch_id]);
        $contract = $this->contract();

        try {
            $this->service->close($contract, '2026-11-15');
            $this->fail('Expected RentalException');
        } catch (RentalException) {
            $this->assertSame(RentalContractStatus::Active, $contract->fresh()->status);
        }

        $reason = Lookup::factory()->create(['type' => LookupType::ReturnReason]);
        $closed = $this->service->close($contract, '2026-11-15', $reason, 'Client travelled', $camp);

        $this->assertSame(RentalContractStatus::Terminated, $closed->status);
        $this->assertSame(WorkerStatus::Available, $closed->worker->fresh()->status);
        $this->assertSame($camp->id, $closed->worker->fresh()->camp_id);
        $this->assertTrue($reason->isInUse());
        $this->assertTrue($camp->isInUse());

        // At the end date: "ended", no reason needed.
        $other = $this->contract();
        $this->assertSame(RentalContractStatus::Ended, $this->service->close($other, '2026-11-30')->status);
    }

    public function test_early_return_charges_the_used_days_with_the_five_pm_rule_and_requests_a_refund(): void
    {
        // 1 Nov → 30 Nov = 30 days, total 2300.
        $contract = $this->contract(start: '2026-11-01');
        $method = Lookup::factory()->create(['type' => LookupType::PaymentMethod]);
        app(PaymentService::class)->record($contract, ['amount' => 2300, 'paid_at' => '2026-11-01', 'payment_method_id' => $method->id]);

        // Back on 10 Nov at 16:00: 10 Nov is not charged → 9 days.
        $before = $this->service->returnQuote($contract->fresh(), Carbon::parse('2026-11-10 16:00'));
        $this->assertSame(9, $before['used_days']);
        $this->assertSame(690.0, $before['charge']);
        $this->assertSame(1610.0, $before['refund']);

        // At 17:30 the day counts → 10 days.
        $this->assertSame(10, $this->service->returnQuote($contract->fresh(), Carbon::parse('2026-11-10 17:30'))['used_days']);

        $reason = Lookup::factory()->create(['type' => LookupType::ReturnReason]);
        $closed = $this->service->close($contract, '2026-11-10 16:00', $reason);

        $this->assertSame(RentalContractStatus::Terminated, $closed->status);
        $this->assertSame('690.00', $closed->final_total);
        $this->assertSame('16:00', $closed->assignments()->sole()->returned_at->format('H:i'));
        $refund = $closed->refunds()->sole();
        $this->assertSame('1610.00', $refund->amount);
        $this->assertSame(RefundStatus::Pending, $refund->status);

        // Approved: the net paid equals the amount due.
        app(RefundService::class)->approve($refund);
        $this->assertSame(0.0, $closed->fresh()->balance());
    }

    public function test_replacement_keeps_the_contract_and_records_both_workers(): void
    {
        $contract = $this->contract(start: '2026-11-01', data: ['departed_at' => '2026-11-01 09:15']);
        $first = $contract->worker;
        $second = $this->worker();
        $reason = Lookup::factory()->create(['type' => LookupType::ReturnReason]);

        $contract = $this->service->replace($contract, $second, '2026-11-05 10:00', '2026-11-05 14:00', $reason, 'Client asked for another worker');

        $this->assertSame(RentalContractStatus::Active, $contract->status);
        $this->assertTrue($contract->worker->is($second));
        $this->assertSame(WorkerStatus::Available, $first->fresh()->status);
        $this->assertSame(WorkerStatus::RentedMonthly, $second->fresh()->status);
        $this->assertSame(
            [['2026-11-01 09:15', '2026-11-05 10:00'], ['2026-11-05 14:00', null]],
            $contract->assignments()->get()->map(fn ($row) => [$row->departed_at->format('Y-m-d H:i'), $row->returned_at?->format('Y-m-d H:i')])->all(),
        );

        // A replacement must be an available monthly worker.
        $this->expectException(RentalException::class);
        $first->forceFill(['rental_type' => WorkerRentalType::Hourly])->save();
        $this->service->replace($contract, $first, '2026-11-06 10:00', '2026-11-06 11:00', $reason);
    }

    public function test_cancel_charges_nothing_and_refunds_what_was_paid(): void
    {
        $contract = $this->contract(start: '2026-11-01');
        $method = Lookup::factory()->create(['type' => LookupType::PaymentMethod]);
        app(PaymentService::class)->record($contract, ['amount' => 1000, 'paid_at' => '2026-11-01', 'payment_method_id' => $method->id]);

        $cancelled = $this->service->cancel($contract, '2026-11-01 18:00', Lookup::factory()->create(['type' => LookupType::ReturnReason]));

        $this->assertSame(RentalContractStatus::Cancelled, $cancelled->status);
        $this->assertSame(0.0, $cancelled->dueTotal());
        $this->assertSame('1000.00', $cancelled->refunds()->sole()->amount);
        $this->assertSame(WorkerStatus::Available, $cancelled->worker->fresh()->status);
    }

    public function test_ending_soon_lists_active_contracts_near_their_end(): void
    {
        $soon = $this->contract(start: today()->subMonth()->addDays(3)->toDateString());
        $later = $this->contract(start: today()->toDateString());

        $ids = $this->service->endingSoon()->pluck('id')->all();

        $this->assertContains($soon->id, $ids);
        $this->assertNotContains($later->id, $ids);
    }

    public function test_status_cannot_be_changed_outside_the_service(): void
    {
        $contract = $this->contract();

        $this->expectException(LogicException::class);
        $contract->forceFill(['status' => RentalContractStatus::Ended])->save();
    }
}
