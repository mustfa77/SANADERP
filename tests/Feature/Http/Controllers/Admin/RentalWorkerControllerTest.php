<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Enums\SponsorType;
use App\Enums\WorkerRentalType;
use App\Enums\WorkerStatus;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Country;
use App\Models\Price;
use App\Models\Shift;
use App\Models\Worker;
use App\Services\Rental\HourlyBookingService;
use App\Services\Rental\RentalContractService;
use App\Services\Workers\WorkerStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalWorkerControllerTest extends TestCase
{
    use RefreshDatabase;

    private Country $philippines;

    private Country $kenya;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();
        $this->philippines = Country::factory()->create(['code' => 'PH']);
        $this->kenya = Country::factory()->create(['code' => 'KE']);
        Price::factory()->create(['country_id' => $this->kenya->id]);
        Price::factory()->hourly()->create(['country_id' => $this->philippines->id, 'price' => 45]);
    }

    private function worker(string $name, WorkerRentalType $type = WorkerRentalType::None, ?Country $country = null, string $branch = 'RYD', SponsorType $sponsor = SponsorType::Company): Worker
    {
        $worker = Worker::factory()->create([
            'name_en' => $name,
            'branch_id' => $this->branch($branch)->id,
            'country_id' => ($country ?? $this->philippines)->id,
            'sponsor_type' => $sponsor,
            'rental_type' => $type,
        ]);

        return $sponsor === SponsorType::Company
            ? app(WorkerStatusService::class)->initialize($worker, WorkerStatus::Available)
            : $worker;
    }

    public function test_tabs_list_monthly_hourly_and_unassigned_workers_of_the_branch(): void
    {
        $user = $this->userWithRole('rental_team', ['RYD']);
        $this->worker('Monthly Mary', WorkerRentalType::Monthly, $this->kenya);
        $this->worker('Hourly Joy', WorkerRentalType::Hourly);
        $this->worker('New Grace');
        $this->worker('Qassim Ana', branch: 'QSM');
        $this->worker('Client Sponsored', sponsor: SponsorType::Client);

        $this->actingAs($user)->withSession(['locale' => 'en'])->get(route('admin.rental.workers', ['tab' => 'monthly']))
            ->assertOk()->assertSee('Monthly Mary')->assertDontSee('Hourly Joy')->assertSee(route('admin.rental.create', ['worker' => Worker::query()->where('name_en', 'Monthly Mary')->value('id')]), false);

        $this->actingAs($user)->withSession(['locale' => 'en'])->get(route('admin.rental.workers', ['tab' => 'hourly']))
            ->assertOk()->assertSee('Hourly Joy')->assertDontSee('Monthly Mary');

        $this->actingAs($user)->withSession(['locale' => 'en'])->get(route('admin.rental.workers', ['tab' => 'unassigned']))
            ->assertOk()->assertSee('New Grace')->assertDontSee('Qassim Ana')->assertDontSee('Client Sponsored')->assertDontSee('Hourly Joy');
    }

    public function test_one_click_adds_a_worker_to_hourly_rental_so_it_can_be_booked(): void
    {
        $user = $this->userWithRole('rental_team', ['RYD']);
        $worker = $this->worker('New Grace');

        $this->actingAs($user)->patch(route('admin.rental.workers.assign', $worker), ['rental_type' => 'hourly'])->assertSessionHas('success');

        $this->assertSame(WorkerRentalType::Hourly, $worker->fresh()->rental_type);
        $this->assertDatabaseHas(AuditLog::class, ['action_key' => 'worker.rental_type_changed', 'target_id' => $worker->id]);
        $this->actingAs($user)->withSession(['locale' => 'en'])->get(route('admin.hourly.create'))->assertSee('New Grace');
    }

    public function test_hourly_needs_a_country_with_an_hourly_price(): void
    {
        $user = $this->userWithRole('rental_team', ['RYD']);
        $kenyan = $this->worker('Kenyan Grace', country: $this->kenya);

        $this->actingAs($user)->patch(route('admin.rental.workers.assign', $kenyan), ['rental_type' => 'hourly'])->assertSessionHas('error');
        $this->assertSame(WorkerRentalType::None, $kenyan->fresh()->rental_type);

        $this->actingAs($user)->patch(route('admin.rental.workers.assign', $kenyan), ['rental_type' => 'monthly'])->assertSessionHas('success');
        $this->assertSame(WorkerRentalType::Monthly, $kenyan->fresh()->rental_type);
    }

    public function test_a_rented_or_booked_worker_cannot_be_removed_from_rental(): void
    {
        $user = $this->userWithRole('rental_team', ['RYD']);
        $client = Client::factory()->create(['branch_id' => $this->branch('RYD')->id]);
        $monthly = $this->worker('Monthly Mary', WorkerRentalType::Monthly, $this->kenya);
        $hourly = $this->worker('Hourly Joy', WorkerRentalType::Hourly);

        app(RentalContractService::class)->create(['client_id' => $client->id, 'worker_id' => $monthly->id, 'starts_at' => today()->toDateString()]);
        app(HourlyBookingService::class)->book([
            'client_id' => $client->id, 'worker_id' => $hourly->id, 'shift_id' => Shift::factory()->create()->id, 'date' => today()->addDay()->toDateString(),
        ]);

        $this->actingAs($user)->patch(route('admin.rental.workers.assign', $monthly), ['rental_type' => 'none'])->assertSessionHas('error');
        $this->actingAs($user)->patch(route('admin.rental.workers.assign', $hourly), ['rental_type' => 'none'])->assertSessionHas('error');

        $this->assertSame(WorkerRentalType::Monthly, $monthly->fresh()->rental_type);
        $this->assertSame(WorkerRentalType::Hourly, $hourly->fresh()->rental_type);
    }

    public function test_permissions_and_branch_scope(): void
    {
        $worker = $this->worker('Qassim Ana', branch: 'QSM');

        $this->actingAs($this->userWithRole('rental_team', ['RYD']))
            ->patch(route('admin.rental.workers.assign', $worker), ['rental_type' => 'monthly'])->assertNotFound();

        $accounts = $this->userWithRole('accounts', ['QSM']);
        $this->actingAs($accounts)->get(route('admin.rental.workers'))->assertForbidden();
        $this->actingAs($accounts)->patch(route('admin.rental.workers.assign', $worker), ['rental_type' => 'monthly'])->assertForbidden();
    }
}
