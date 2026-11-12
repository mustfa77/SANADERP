<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Enums\LookupType;
use App\Models\HourlyBooking;
use App\Models\Lookup;
use App\Models\Payment;
use App\Models\RentalContract;
use Database\Seeders\RentalDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalCalendarReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();
    }

    public function test_demo_seeder_creates_contracts_bookings_and_payments_once(): void
    {
        $this->seed(RentalDemoSeeder::class);

        $this->assertGreaterThan(0, RentalContract::query()->withoutGlobalScopes()->where('status', 'active')->count());
        $this->assertGreaterThan(0, RentalContract::query()->withoutGlobalScopes()->where('status', 'renewed')->count());
        $this->assertGreaterThan(0, HourlyBooking::query()->withoutGlobalScopes()->where('status', 'completed')->count());
        $this->assertGreaterThan(0, Payment::query()->withoutGlobalScopes()->count());

        $contracts = RentalContract::query()->withoutGlobalScopes()->count();
        $this->seed(RentalDemoSeeder::class);
        $this->assertSame($contracts, RentalContract::query()->withoutGlobalScopes()->count());
    }

    public function test_calendar_feed_returns_bookings_and_contract_dates_of_the_users_branches(): void
    {
        $this->seed(RentalDemoSeeder::class);
        $user = $this->userWithRole('rental_team', ['RYD']);
        $riyadhBooking = HourlyBooking::query()->withoutGlobalScopes()->where('branch_id', $this->branch('RYD')->id)->where('starts_at', '>', now())->first();
        $otherBooking = HourlyBooking::query()->withoutGlobalScopes()->where('branch_id', '!=', $this->branch('RYD')->id)->where('starts_at', '>', now())->first();

        $this->actingAs($user)->get(route('admin.rental.calendar'))->assertOk()->assertSee('rental-calendar', false);

        $events = $this->actingAs($user)->getJson(route('admin.rental.calendar.events', [
            'start' => today()->subDays(10)->toDateString(), 'end' => today()->addDays(30)->toDateString(),
        ]))->assertOk()->json();

        $ids = collect($events)->pluck('id');
        $this->assertContains('hb-'.$riyadhBooking->id, $ids);
        $this->assertNotContains('hb-'.$otherBooking?->id, $ids);
        $this->assertTrue($ids->contains(fn (string $id): bool => str_starts_with($id, 'mr-')));

        // Only bookings when asked.
        $onlyBookings = $this->actingAs($user)->getJson(route('admin.rental.calendar.events', [
            'start' => today()->subDays(10)->toDateString(), 'end' => today()->addDays(30)->toDateString(), 'types' => ['bookings'],
        ]))->json();
        $this->assertFalse(collect($onlyBookings)->contains(fn (array $event): bool => str_starts_with($event['id'], 'mr-')));

        $this->actingAs($this->userWithRole('accounts', ['RYD']))->get(route('admin.rental.calendar'))->assertForbidden();
    }

    public function test_reports_show_operations_and_amounts_only_with_the_financial_permission(): void
    {
        $this->seed(RentalDemoSeeder::class);

        $this->actingAs($this->userWithRole('super_admin'))->withSession(['locale' => 'en'])->get(route('admin.rental.reports'))
            ->assertOk()
            ->assertSee(__('rental.reports.operations.active_now', [], 'en'))
            ->assertSee(__('rental.reports.sections.outstanding', [], 'en'))
            ->assertSee('daily-chart', false);

        // The branch manager sees reports but not the amounts.
        $manager = $this->userWithRole('branch_manager', ['RYD']);
        $this->actingAs($manager)->withSession(['locale' => 'en'])->get(route('admin.rental.reports'))
            ->assertOk()
            ->assertSee(__('rental.reports.no_financial', [], 'en'))
            ->assertDontSee('daily-chart', false);

        $this->actingAs($this->userWithRole('rental_team', ['RYD']))->get(route('admin.rental.reports'))->assertForbidden();
    }

    public function test_calendar_date_opens_the_booking_form_on_that_date(): void
    {
        Lookup::factory()->create(['type' => LookupType::PaymentMethod]);
        $date = today()->addDays(4)->toDateString();

        $this->actingAs($this->userWithRole('rental_team', ['RYD']))->get(route('admin.hourly.create', ['date' => $date]))
            ->assertOk()
            ->assertSee('value="'.$date.'"', false);
    }
}
