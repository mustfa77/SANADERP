<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\AuditLog;
use App\Models\Country;
use App\Models\CountryTravelStage;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentTypeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();
    }

    public function test_managing_document_types_needs_permission(): void
    {
        $user = $this->userWithRole('rental_team', ['RYD']);
        $user->givePermissionTo('settings.view');

        $this->actingAs($user)->get(route('admin.document-types.index'))->assertOk();
        $this->actingAs($user)->post(route('admin.document-types.store'), [])->assertForbidden();
    }

    public function test_adds_country_document_type_shown_on_the_country_page(): void
    {
        $admin = $this->userWithRole('super_admin');
        $country = Country::factory()->create();

        $this->actingAs($admin)->post(route('admin.document-types.store'), [
            'country_id' => $country->id,
            'name_ar' => 'شهادة OEC', 'name_en' => 'OEC certificate',
            'is_required' => '1', 'include_in_authority_file' => '1', 'is_active' => '1',
        ])->assertRedirect(route('admin.countries.show', [$country, 'tab' => 'documents']));

        $type = DocumentType::query()->sole();
        $this->assertTrue($type->is_required && $type->include_in_authority_file);
        $this->assertDatabaseHas(AuditLog::class, ['action_key' => 'document_type.created', 'target_id' => $type->id]);

        $this->actingAs($admin)->withSession(['locale' => 'en'])
            ->get(route('admin.countries.show', [$country, 'tab' => 'documents']))
            ->assertOk()
            ->assertSee('OEC certificate');
    }

    public function test_move_and_toggle_document_types(): void
    {
        $admin = $this->userWithRole('super_admin');
        $first = DocumentType::factory()->create();
        $second = DocumentType::factory()->create();

        $this->actingAs($admin)->patch(route('admin.document-types.move', $second), ['direction' => 'up']);
        $this->assertSame([$second->id, $first->id], DocumentType::query()->whereNull('country_id')->ordered()->pluck('id')->all());

        $this->actingAs($admin)->patch(route('admin.document-types.toggle', $first));
        $this->assertFalse($first->fresh()->is_active);
    }

    public function test_document_type_with_uploaded_files_cannot_be_deleted(): void
    {
        $admin = $this->userWithRole('super_admin');
        $type = DocumentType::factory()->create();
        Document::factory()->create(['document_type_id' => $type->id]);

        $this->actingAs($admin)->delete(route('admin.document-types.destroy', $type))->assertSessionHas('error');
        $this->assertNotSoftDeleted($type);
    }

    public function test_travel_stages_are_managed_from_the_country_page(): void
    {
        $admin = $this->userWithRole('super_admin');
        $country = Country::factory()->create();

        $this->actingAs($admin)->post(route('admin.countries.stages.store', $country), ['name_ar' => 'الفحص', 'name_en' => 'Medical', 'max_days' => 7, 'is_active' => '1']);
        $this->actingAs($admin)->post(route('admin.countries.stages.store', $country), ['name_ar' => 'التأشيرة', 'name_en' => 'Visa', 'is_active' => '1']);
        [$medical, $visa] = $country->travelStages()->get()->all();

        $this->actingAs($admin)->patch(route('admin.countries.stages.move', [$country, $visa]), ['direction' => 'up']);
        $this->assertSame(['Visa', 'Medical'], $country->travelStages()->pluck('name_en')->all());

        // A stage of another country is not reachable through this country.
        $foreign = CountryTravelStage::factory()->create();
        $this->actingAs($admin)->get(route('admin.countries.stages.edit', [$country, $foreign]))->assertNotFound();

        $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.countries.show', $country))
            ->assertOk()->assertSeeInOrder(['Visa', 'Medical']);
    }

    public function test_alert_settings_are_saved(): void
    {
        $admin = $this->userWithRole('super_admin');
        $payload = collect(Setting::ALERTS)->map(fn (array $definition): int => $definition['default'])->all();

        $this->actingAs($admin)->put(route('admin.settings.alerts'), [...$payload, 'days_before_iqama_expiry' => 45])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'alerts']));

        $this->assertSame(45, Setting::integer('days_before_iqama_expiry'));
    }
}
