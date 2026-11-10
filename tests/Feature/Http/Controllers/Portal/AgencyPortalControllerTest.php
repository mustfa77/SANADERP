<?php

namespace Tests\Feature\Http\Controllers\Portal;

use App\Models\Agency;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgencyPortalControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();
    }

    public function test_external_agency_sees_only_its_own_candidates(): void
    {
        $agency = Agency::factory()->create();
        $otherAgency = Agency::factory()->create();
        Candidate::factory()->for($agency)->create(['name_en' => 'Own Candidate']);
        Candidate::factory()->for($otherAgency)->create(['name_en' => 'Foreign Candidate']);
        $user = User::factory()->externalAgency($agency)->create(['preferred_language' => 'en']);

        $this->actingAs($user)
            ->get(route('agency.candidates.index'))
            ->assertOk()
            ->assertSee('Own Candidate')
            ->assertDontSee('Foreign Candidate');
    }

    public function test_external_agency_cannot_open_the_admin_dashboard(): void
    {
        $user = User::factory()->externalAgency()->create();

        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_internal_staff_cannot_open_the_agency_portal(): void
    {
        $this->actingAs($this->userWithRole('super_admin'))
            ->get(route('agency.candidates.index'))
            ->assertForbidden();
    }

    public function test_driver_can_only_reach_driver_routes(): void
    {
        $driver = User::factory()->driver()->create();

        $this->actingAs($driver)->get(route('driver.trips.index'))->assertOk();
        $this->actingAs($driver)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($driver)->get(route('agency.candidates.index'))->assertForbidden();
    }
}
