<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Enums\RentalType;
use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\Camp;
use App\Models\Country;
use App\Models\Price;
use App\Models\Profession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();
    }

    public function test_reference_screens_need_permission(): void
    {
        $user = $this->userWithRole('rental_team', ['RYD']);

        $this->actingAs($user)->get(route('admin.reference.countries.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.reference.professions.store'), [])->assertForbidden();

        $user->givePermissionTo('settings.view');
        $this->actingAs($user)->get(route('admin.reference.countries.index'))->assertOk()
            ->assertDontSee(route('admin.reference.countries.create'));
        $this->actingAs($user)->get(route('admin.reference.countries.create'))->assertForbidden();
    }

    public function test_creates_country_and_audits_it(): void
    {
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->post(route('admin.reference.countries.store'), [
            'name_ar' => 'بنغلاديش', 'name_en' => 'Bangladesh',
            'nationality_ar' => 'بنغلاديشية', 'nationality_en' => 'Bangladeshi',
            'code' => 'BD', 'is_active' => '1',
        ])->assertRedirect();

        $country = Country::query()->where('code', 'BD')->sole();
        $this->assertTrue($country->is_active);
        $this->assertDatabaseHas(AuditLog::class, ['action_key' => 'reference.created', 'target_id' => $country->id]);

        $this->actingAs($admin)->withSession(['locale' => 'en'])
            ->get(route('admin.reference.countries.index', ['search' => 'Bangla']))
            ->assertSee('Bangladesh');
    }

    public function test_requires_both_languages(): void
    {
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->post(route('admin.reference.professions.store'), ['name_ar' => 'سائق'])
            ->assertSessionHasErrors('name_en');
    }

    public function test_deleting_a_used_record_is_blocked(): void
    {
        $admin = $this->userWithRole('super_admin');
        $country = Country::factory()->create();
        Agency::factory()->create(['country_id' => $country->id]);

        $this->actingAs($admin)->delete(route('admin.reference.countries.destroy', $country))
            ->assertSessionHas('error', __('reference.flash.in_use', ['item' => __('reference.resources.countries.singular')]));
        $this->assertNotSoftDeleted($country);

        $unused = Profession::factory()->create();
        $this->actingAs($admin)->delete(route('admin.reference.professions.destroy', $unused))->assertSessionHas('success');
        $this->assertSoftDeleted($unused);
    }

    public function test_deactivate_and_activate(): void
    {
        $admin = $this->userWithRole('super_admin');
        $profession = Profession::factory()->create();

        $this->actingAs($admin)->patch(route('admin.reference.professions.deactivate', $profession));
        $this->assertFalse($profession->fresh()->is_active);
        $this->assertDatabaseHas(AuditLog::class, ['action_key' => 'reference.deactivated', 'target_id' => $profession->id]);

        $this->actingAs($admin)->patch(route('admin.reference.professions.activate', $profession));
        $this->assertTrue($profession->fresh()->is_active);
    }

    public function test_only_one_active_price_per_country_and_rental_type(): void
    {
        $admin = $this->userWithRole('super_admin');
        $country = Country::factory()->create();
        Price::factory()->create(['country_id' => $country->id, 'rental_type' => RentalType::Monthly]);
        $payload = ['country_id' => $country->id, 'rental_type' => 'monthly', 'price' => 2500, 'vat_percent' => 15];

        $this->actingAs($admin)->post(route('admin.reference.prices.store'), [...$payload, 'is_active' => '1'])
            ->assertSessionHasErrors('is_active');

        $this->actingAs($admin)->post(route('admin.reference.prices.store'), [...$payload, 'is_active' => '0'])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.reference.prices.store'), [...$payload, 'rental_type' => 'hourly', 'price' => 35, 'is_active' => '1'])->assertRedirect();

        $inactive = Price::query()->where('is_active', false)->sole();
        $this->actingAs($admin)->patch(route('admin.reference.prices.activate', $inactive))->assertSessionHas('error');
        $this->assertFalse($inactive->fresh()->is_active);
    }

    public function test_camps_respect_branch_scope(): void
    {
        $riyadhCamp = Camp::factory()->create(['branch_id' => $this->branch('RYD')->id, 'name_en' => 'Riyadh camp']);
        $qassimCamp = Camp::factory()->create(['branch_id' => $this->branch('QSM')->id, 'name_en' => 'Qassim camp']);

        $supervisor = $this->userWithRole('rental_team', ['RYD']);
        $supervisor->givePermissionTo(['camps.view', 'camps.edit', 'camps.create']);

        $this->actingAs($supervisor)->withSession(['locale' => 'en'])->get(route('admin.reference.camps.index'))
            ->assertOk()
            ->assertSee('Riyadh camp')
            ->assertDontSee('Qassim camp');

        $this->actingAs($supervisor)->get(route('admin.reference.camps.edit', $qassimCamp))->assertNotFound();
        $this->actingAs($supervisor)->patch(route('admin.reference.camps.deactivate', $qassimCamp))->assertNotFound();
        $this->actingAs($supervisor)->get(route('admin.reference.camps.edit', $riyadhCamp))->assertOk();

        // A camp cannot be created in a branch outside the user's branches.
        $this->actingAs($supervisor)->post(route('admin.reference.camps.store'), [
            'name_ar' => 'سكن', 'name_en' => 'New camp', 'branch_id' => $this->branch('QSM')->id,
            'type' => 'rental', 'capacity' => 20, 'is_active' => '1',
        ])->assertSessionHasErrors('branch_id');
    }

    public function test_sortable_lists_can_be_reordered(): void
    {
        $admin = $this->userWithRole('super_admin');
        $first = Profession::factory()->create();
        $second = Profession::factory()->create();

        $this->actingAs($admin)->patch(route('admin.reference.professions.move', $second), ['direction' => 'up']);

        $this->assertSame([$second->id, $first->id], Profession::query()->ordered()->pluck('id')->all());
    }
}
