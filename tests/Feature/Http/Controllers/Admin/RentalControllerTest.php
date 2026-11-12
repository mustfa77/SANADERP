<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Enums\HourlyBookingStatus;
use App\Enums\LookupType;
use App\Enums\RefundStatus;
use App\Enums\RentalContractStatus;
use App\Enums\SponsorType;
use App\Enums\WorkerRentalType;
use App\Enums\WorkerStatus;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Country;
use App\Models\HourlyBooking;
use App\Models\Lookup;
use App\Models\Payment;
use App\Models\Price;
use App\Models\Refund;
use App\Models\RentalContract;
use App\Models\Shift;
use App\Models\Worker;
use App\Services\Rental\HourlyBookingService;
use App\Services\Rental\RentalContractService;
use App\Services\Workers\WorkerStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RentalControllerTest extends TestCase
{
    use RefreshDatabase;

    private Country $philippines;

    private Country $kenya;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seedAccess();
        $this->philippines = Country::factory()->create(['code' => 'PH']);
        $this->kenya = Country::factory()->create(['code' => 'KE']);
        Price::factory()->create(['country_id' => $this->kenya->id, 'price' => 1800]);
        Price::factory()->hourly()->create(['country_id' => $this->philippines->id, 'price' => 45]);
    }

    private function client(string $branch = 'RYD'): Client
    {
        return Client::factory()->create(['branch_id' => $this->branch($branch)->id]);
    }

    private function worker(WorkerRentalType $type, string $name, string $branch = 'RYD', ?Country $country = null): Worker
    {
        $worker = Worker::factory()->create([
            'name_en' => $name,
            'branch_id' => $this->branch($branch)->id,
            'country_id' => ($country ?? ($type === WorkerRentalType::Hourly ? $this->philippines : $this->kenya))->id,
            'sponsor_type' => SponsorType::Company,
            'rental_type' => $type,
        ]);

        return app(WorkerStatusService::class)->initialize($worker, WorkerStatus::Available);
    }

    private function contract(string $branch = 'RYD'): RentalContract
    {
        return app(RentalContractService::class)->create([
            'client_id' => $this->client($branch)->id,
            'worker_id' => $this->worker(WorkerRentalType::Monthly, 'Monthly '.$branch, $branch)->id,
            'starts_at' => today()->toDateString(),
        ]);
    }

    private function paymentMethod(): Lookup
    {
        return Lookup::factory()->create(['type' => LookupType::PaymentMethod, 'name_en' => 'Bank transfer']);
    }

    public function test_sidebar_links_to_rental_screens(): void
    {
        $this->actingAs($this->userWithRole('rental_team', ['RYD']))->get(route('admin.rental.index'))
            ->assertOk()
            ->assertSee(route('admin.rental.index'), false)
            ->assertSee(route('admin.hourly.index'), false);
    }

    public function test_monthly_contract_is_created_from_the_screen(): void
    {
        $user = $this->userWithRole('rental_team', ['RYD']);
        $client = $this->client();
        $worker = $this->worker(WorkerRentalType::Monthly, 'Monthly Mary');
        $this->worker(WorkerRentalType::Hourly, 'Hourly Joy');

        $this->actingAs($user)->withSession(['locale' => 'en'])->get(route('admin.rental.create'))
            ->assertOk()
            ->assertSee('Monthly Mary')
            ->assertDontSee('Hourly Joy');

        $this->actingAs($user)->post(route('admin.rental.store'), [
            'client_id' => $client->id, 'worker_id' => $worker->id, 'starts_at' => today()->toDateString(),
        ])->assertRedirect();

        $contract = RentalContract::query()->sole();
        $this->assertSame('1800.00', $contract->price);
        $this->assertSame(WorkerStatus::RentedMonthly, $worker->fresh()->status);
        $this->assertDatabaseHas(AuditLog::class, ['action_key' => 'rental.created', 'target_id' => $contract->id]);

        $this->actingAs($user)->withSession(['locale' => 'en'])->get(route('admin.rental.show', $contract))
            ->assertOk()
            ->assertSee($contract->number)
            ->assertSee(route('admin.rental.renew', $contract), false);
        $this->actingAs($user)->get(route('admin.rental.pdf', $contract))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_renew_and_close_from_the_contract_page(): void
    {
        $user = $this->userWithRole('rental_team', ['RYD']);
        $contract = $this->contract();

        $this->actingAs($user)->post(route('admin.rental.renew', $contract), ['price' => 1900])->assertSessionHas('success');
        $renewal = RentalContract::query()->where('renewed_from_id', $contract->id)->sole();

        // Early return without a reason is refused.
        $this->actingAs($user)->post(route('admin.rental.close', $renewal), ['returned_at' => now()->format('Y-m-d H:i')])->assertSessionHas('error');
        $this->assertSame(RentalContractStatus::Active, $renewal->fresh()->status);
    }

    public function test_contract_page_shows_departure_time_and_the_return_actions(): void
    {
        $user = $this->userWithRole('rental_team', ['RYD']);
        $contract = $this->contract();
        $this->worker(WorkerRentalType::Monthly, 'Spare Worker');

        $this->actingAs($user)->withSession(['locale' => 'en'])->get(route('admin.rental.show', $contract))
            ->assertOk()
            ->assertSee($contract->assignments()->sole()->departed_at->format('Y-m-d H:i'))
            ->assertSee(route('admin.rental.replace', $contract), false)
            ->assertSee(route('admin.rental.cancel', $contract), false);
    }

    public function test_replace_from_the_screen_keeps_the_contract_with_the_new_worker(): void
    {
        $user = $this->userWithRole('rental_team', ['RYD']);
        $contract = $this->contract();
        $old = $contract->worker;
        $new = $this->worker(WorkerRentalType::Monthly, 'Replacement Ann');
        $reason = Lookup::factory()->create(['type' => LookupType::ReturnReason]);

        $this->actingAs($user)->post(route('admin.rental.replace', $contract), [
            'returned_at' => now()->format('Y-m-d H:i'), 'departed_at' => now()->format('Y-m-d H:i'),
            'new_worker_id' => $new->id, 'close_reason_id' => $reason->id,
        ])->assertSessionHas('success');

        $this->assertTrue($contract->fresh()->worker->is($new));
        $this->assertSame(WorkerStatus::Available, $old->fresh()->status);
        $this->assertSame(WorkerStatus::RentedMonthly, $new->fresh()->status);
        $this->assertSame(2, $contract->assignments()->count());
    }

    public function test_cancel_requests_a_refund_that_finance_approves(): void
    {
        $contract = $this->contract();
        $accounts = $this->userWithRole('accounts', ['RYD']);
        $accounts->givePermissionTo('rental_contracts.view');
        $this->actingAs($accounts)->post(route('admin.payments.store', ['rental', $contract->id]), [
            'amount' => 500, 'paid_at' => today()->toDateString(), 'payment_method_id' => $this->paymentMethod()->id,
        ]);

        $user = $this->userWithRole('rental_team', ['RYD']);
        $this->actingAs($user)->post(route('admin.rental.cancel', $contract), [
            'returned_at' => now()->format('Y-m-d H:i'),
            'close_reason_id' => Lookup::factory()->create(['type' => LookupType::ReturnReason])->id,
        ])->assertSessionHas('success');

        $this->assertSame(RentalContractStatus::Cancelled, $contract->fresh()->status);
        $refund = Refund::query()->sole();
        $this->assertSame('500.00', $refund->amount);
        $this->assertSame(RefundStatus::Pending, $refund->status);

        // The rental team cannot approve; the finance manager can.
        $this->actingAs($user)->post(route('admin.refunds.approve', $refund))->assertForbidden();
        $manager = $this->userWithRole('finance_manager', ['RYD']);
        $this->actingAs($manager)->withSession(['locale' => 'en'])->get(route('admin.refunds.index'))->assertOk()->assertSee($contract->number);
        $this->actingAs($manager)->post(route('admin.refunds.approve', $refund))->assertSessionHas('success');

        $this->assertSame(RefundStatus::Approved, $refund->fresh()->status);
        $this->assertSame(0.0, $contract->fresh()->paidAmount());
        $this->assertSame(0.0, $contract->fresh()->balance());
    }

    public function test_hourly_departure_and_return_are_recorded_with_the_delay(): void
    {
        $user = $this->userWithRole('rental_team', ['RYD']);
        $this->travelTo(today()->setTime(7, 0));
        $booking = app(HourlyBookingService::class)->book([
            'client_id' => $this->client()->id,
            'worker_id' => $this->worker(WorkerRentalType::Hourly, 'Hourly Joy')->id,
            'shift_id' => Shift::factory()->create(['start_time' => '08:00:00', 'hours' => 4])->id,
            'date' => today()->toDateString(),
        ])->sole();

        $this->travelTo(today()->setTime(13, 0));
        $this->actingAs($user)->post(route('admin.hourly.depart', $booking), ['departed_at' => today()->format('Y-m-d').' 07:20'])->assertSessionHas('success');
        $this->actingAs($user)->post(route('admin.hourly.complete', $booking), ['returned_at' => today()->format('Y-m-d').' 12:45'])->assertSessionHas('success');

        $booking->refresh();
        $this->assertSame(HourlyBookingStatus::Completed, $booking->status);
        $this->assertSame('07:20', $booking->departed_at->format('H:i'));
        $this->assertSame(45, $booking->lateMinutes());
        $this->actingAs($user)->withSession(['locale' => 'en'])->get(route('admin.hourly.show', $booking))->assertSee(__('hourly.late', ['minutes' => 45], 'en'));
    }

    public function test_another_branchs_contract_is_a_404(): void
    {
        $contract = $this->contract('QSM');
        $user = $this->userWithRole('rental_team', ['RYD']);

        $this->actingAs($user)->get(route('admin.rental.show', $contract))->assertNotFound();
        $this->actingAs($user)->post(route('admin.rental.renew', $contract))->assertNotFound();
    }

    public function test_hourly_booking_lists_only_hourly_workers_and_books_several_days(): void
    {
        $user = $this->userWithRole('rental_team', ['RYD']);
        $client = $this->client();
        $hourly = $this->worker(WorkerRentalType::Hourly, 'Hourly Joy');
        $this->worker(WorkerRentalType::Hourly, 'Kenyan Hourly', country: $this->kenya);
        $this->worker(WorkerRentalType::Monthly, 'Monthly Mary');
        $shift = Shift::factory()->create(['start_time' => '09:00:00', 'hours' => 5]);

        $this->actingAs($user)->withSession(['locale' => 'en'])->get(route('admin.hourly.create'))
            ->assertOk()
            ->assertSee('Hourly Joy')
            ->assertDontSee('Kenyan Hourly')
            ->assertDontSee('Monthly Mary');

        $this->actingAs($user)->post(route('admin.hourly.store'), [
            'client_id' => $client->id, 'worker_id' => $hourly->id, 'shift_id' => $shift->id,
            'date' => today()->addDay()->toDateString(), 'days' => 3, 'address' => 'Al Malqa',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(3, HourlyBooking::query()->count());
        // 45 × 5 = 225 + 15% = 258.75
        $this->assertSame('258.75', HourlyBooking::query()->first()->total);

        $this->actingAs($user)->withSession(['locale' => 'en'])->get(route('admin.hourly.index', ['from' => today()->toDateString(), 'to' => today()->addDays(5)->toDateString()]))
            ->assertOk()
            ->assertSee('Hourly Joy')
            ->assertSee('09:00');
    }

    public function test_hourly_booking_can_be_cancelled_with_a_reason(): void
    {
        $user = $this->userWithRole('rental_team', ['RYD']);
        $booking = app(HourlyBookingService::class)->book([
            'client_id' => $this->client()->id,
            'worker_id' => $this->worker(WorkerRentalType::Hourly, 'Hourly Joy')->id,
            'shift_id' => Shift::factory()->create()->id,
            'date' => today()->addDay()->toDateString(),
        ])->sole();

        $this->actingAs($user)->post(route('admin.hourly.cancel', $booking), [])->assertSessionHasErrors('cancel_reason_id');
        $this->actingAs($user)->post(route('admin.hourly.cancel', $booking), [
            'cancel_reason_id' => Lookup::factory()->create(['type' => LookupType::CancelReason])->id,
        ])->assertSessionHas('success');

        $this->assertSame(HourlyBookingStatus::Cancelled, $booking->fresh()->status);
    }

    public function test_payment_with_transfer_receipt_is_recorded_verified_and_cannot_exceed_the_balance(): void
    {
        $contract = $this->contract();
        $method = $this->paymentMethod();
        $accounts = $this->userWithRole('accounts', ['RYD']);
        $accounts->givePermissionTo('rental_contracts.view');

        $this->actingAs($accounts)->post(route('admin.payments.store', ['rental', $contract->id]), [
            'amount' => 1000, 'paid_at' => today()->toDateString(), 'payment_method_id' => $method->id, 'reference' => 'TRX-77',
            'receipt' => UploadedFile::fake()->create('transfer.pdf', 50, 'application/pdf'),
        ])->assertSessionHas('success');

        $payment = Payment::query()->sole();
        Storage::disk('local')->assertExists($payment->receipt_path);
        $this->assertSame('transfer.pdf', $payment->receipt_name);
        $this->assertEqualsWithDelta((float) $contract->total - 1000, $contract->fresh()->balance(), 0.001);
        $this->assertDatabaseHas(AuditLog::class, ['action_key' => 'payment.recorded', 'target_id' => $payment->id]);

        $this->actingAs($accounts)->get(route('admin.payments.receipt', $payment))->assertOk();

        // More than the balance is refused.
        $this->actingAs($accounts)->post(route('admin.payments.store', ['rental', $contract->id]), [
            'amount' => 99999, 'paid_at' => today()->toDateString(), 'payment_method_id' => $method->id,
        ])->assertSessionHas('error');
        $this->assertSame(1, Payment::query()->count());

        $this->actingAs($accounts)->post(route('admin.payments.verify', $payment))->assertSessionHas('success');
        $this->assertNotNull($payment->fresh()->verified_at);

        // A verified payment cannot be removed.
        $this->actingAs($accounts)->delete(route('admin.payments.destroy', $payment))->assertForbidden();
        $this->assertNotSoftDeleted($payment);
    }

    public function test_payment_permissions_and_branch_scope(): void
    {
        $contract = $this->contract();
        $method = $this->paymentMethod();
        $payload = ['amount' => 100, 'paid_at' => today()->toDateString(), 'payment_method_id' => $method->id];

        // The rental team has no payments.create by default.
        $this->actingAs($this->userWithRole('rental_team', ['RYD']))
            ->post(route('admin.payments.store', ['rental', $contract->id]), $payload)->assertForbidden();

        // Accounts of another branch cannot reach the contract.
        $qassim = $this->userWithRole('accounts', ['QSM']);
        $qassim->givePermissionTo('rental_contracts.view');
        $this->actingAs($qassim)->post(route('admin.payments.store', ['rental', $contract->id]), $payload)->assertNotFound();

        $accounts = $this->userWithRole('accounts', ['RYD']);
        $accounts->givePermissionTo('rental_contracts.view');
        $this->actingAs($accounts)->post(route('admin.payments.store', ['rental', $contract->id]), $payload);
        $payment = Payment::query()->sole();
        $this->actingAs($qassim)->get(route('admin.payments.receipt', $payment))->assertNotFound();

        // Accounts cannot remove payments; the finance manager can remove an unverified one.
        $this->actingAs($accounts)->delete(route('admin.payments.destroy', $payment))->assertForbidden();
        $manager = $this->userWithRole('finance_manager', ['RYD']);
        $manager->givePermissionTo('rental_contracts.view');
        $this->actingAs($manager)->delete(route('admin.payments.destroy', $payment))->assertSessionHas('success');
        $this->assertSoftDeleted($payment);
    }

    public function test_client_and_worker_profiles_show_the_rental_tab(): void
    {
        $admin = $this->userWithRole('super_admin');
        $contract = $this->contract();

        $this->actingAs($admin)->get(route('admin.clients.show', [$contract->client, 'tab' => 'rental']))->assertOk()->assertSee($contract->number);
        $this->actingAs($admin)->get(route('admin.workers.show', [$contract->worker, 'tab' => 'rental']))->assertOk()->assertSee($contract->number);
    }
}
