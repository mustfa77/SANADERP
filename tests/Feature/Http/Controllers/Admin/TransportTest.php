<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Enums\LookupType;
use App\Enums\SponsorType;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Enums\UserType;
use App\Enums\WorkerRentalType;
use App\Enums\WorkerStatus;
use App\Models\Client;
use App\Models\Country;
use App\Models\DriverProfile;
use App\Models\HourlyBooking;
use App\Models\Lookup;
use App\Models\Price;
use App\Models\Shift;
use App\Models\Trip;
use App\Models\User;
use App\Models\Worker;
use App\Services\Rental\HourlyBookingService;
use App\Services\Workers\WorkerStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();
    }

    private function driver(string $branch = 'RYD', string $name = 'Driver Ahmed'): User
    {
        $user = User::factory()->driver()->create(['name_ar' => $name, 'name_en' => $name]);
        $user->branches()->sync([$this->branch($branch)->id]);
        $profile = new DriverProfile(['branch_id' => $this->branch($branch)->id, 'vehicle_model' => 'Hiace', 'is_active' => true]);
        $profile->forceFill(['user_id' => $user->id])->save();

        return $user;
    }

    private function booking(): HourlyBooking
    {
        $country = Country::factory()->create(['code' => 'PH']);
        Price::factory()->hourly()->create(['country_id' => $country->id, 'price' => 40]);
        $worker = Worker::factory()->create(['branch_id' => $this->branch('RYD')->id, 'country_id' => $country->id, 'sponsor_type' => SponsorType::Company, 'rental_type' => WorkerRentalType::Hourly]);
        app(WorkerStatusService::class)->initialize($worker, WorkerStatus::Available);

        return app(HourlyBookingService::class)->book([
            'client_id' => Client::factory()->create(['branch_id' => $this->branch('RYD')->id])->id,
            'worker_id' => $worker->id,
            'shift_id' => Shift::factory()->create(['start_time' => '08:00:00', 'move_time' => '07:15:00', 'hours' => 4])->id,
            'date' => today()->addDay()->toDateString(),
            'address' => 'Al Malqa',
        ])->sole();
    }

    public function test_an_hourly_booking_creates_drop_off_and_return_trips_and_cancelling_it_cancels_them(): void
    {
        $booking = $this->booking();

        $trips = Trip::query()->orderBy('scheduled_at')->get();
        $this->assertSame([TripType::HourlyDropoff, TripType::HourlyPickup], $trips->pluck('type')->all());
        $this->assertSame('07:15', $trips[0]->scheduled_at->format('H:i'));
        $this->assertSame('12:00', $trips[1]->scheduled_at->format('H:i'));
        $this->assertStringContainsString('Al Malqa', $trips[0]->to_place);

        app(HourlyBookingService::class)->cancel($booking, Lookup::factory()->create(['type' => LookupType::CancelReason]));
        $this->assertSame([TripStatus::Cancelled, TripStatus::Cancelled], Trip::query()->pluck('status')->all());
    }

    public function test_coordinator_assigns_a_driver_who_starts_and_completes_from_the_portal(): void
    {
        $this->booking();
        $driver = $this->driver();
        $trip = Trip::query()->orderBy('scheduled_at')->first();
        $coordinator = $this->userWithRole('transport_coordinator', ['RYD']);

        $this->actingAs($coordinator)->withSession(['locale' => 'en'])->get(route('admin.trips.index', ['date' => $trip->scheduled_at->toDateString()]))
            ->assertOk()->assertSee($trip->number)->assertSee('Driver Ahmed');

        $this->actingAs($coordinator)->post(route('admin.trips.assign', $trip), ['driver_id' => $driver->id])->assertSessionHas('success');
        $this->assertSame($driver->id, $trip->fresh()->driver_id);

        $this->actingAs($driver)->get(route('driver.trips.index'))->assertOk()->assertSee($trip->number);
        $this->actingAs($driver)->post(route('driver.trips.start', $trip->id))->assertSessionHas('success');
        $this->actingAs($driver)->post(route('driver.trips.complete', $trip->id))->assertSessionHas('success');

        $trip->refresh();
        $this->assertSame(TripStatus::Completed, $trip->status);
        $this->assertNotNull($trip->started_at);

        // Another driver cannot touch it.
        $other = $this->driver(name: 'Driver Two');
        $this->actingAs($other)->post(route('driver.trips.complete', $trip->id))->assertNotFound();
    }

    public function test_a_driver_of_another_branch_cannot_be_assigned(): void
    {
        $this->booking();
        $qassimDriver = $this->driver('QSM', 'Qassim Driver');
        $trip = Trip::query()->first();

        $this->actingAs($this->userWithRole('super_admin'))->post(route('admin.trips.assign', $trip), ['driver_id' => $qassimDriver->id])->assertSessionHas('error');
        $this->assertNull($trip->fresh()->driver_id);
    }

    public function test_manual_trip_and_driver_creation(): void
    {
        $coordinator = $this->userWithRole('transport_coordinator', ['RYD']);

        $this->actingAs($coordinator)->post(route('admin.drivers.store'), [
            'name_ar' => 'سائق جديد', 'email' => 'new.driver@example.com', 'phone' => '0550000000', 'password' => 'Secret123',
            'branch_id' => $this->branch('RYD')->id, 'vehicle_model' => 'Hiace', 'vehicle_plate' => 'ABC 123',
        ])->assertRedirect(route('admin.drivers.index'));

        $driver = User::query()->where('email', 'new.driver@example.com')->sole();
        $this->assertSame(UserType::Driver, $driver->user_type);
        $this->assertTrue($driver->must_change_password);
        $this->assertSame('ABC 123', $driver->driverProfile->vehicle_plate);

        $this->actingAs($coordinator)->post(route('admin.trips.store'), [
            'branch_id' => $this->branch('RYD')->id, 'scheduled_at' => today()->setTime(15, 0)->format('Y-m-d H:i'),
            'from_place' => 'Office', 'to_place' => 'Hospital', 'driver_id' => $driver->id,
        ])->assertRedirect();

        $trip = Trip::query()->sole();
        $this->assertSame(TripType::Manual, $trip->type);
        $this->assertSame($driver->id, $trip->driver_id);

        // The rental team cannot see trips.
        $this->actingAs($this->userWithRole('rental_team', ['RYD']))->get(route('admin.trips.index'))->assertForbidden();
    }
}
