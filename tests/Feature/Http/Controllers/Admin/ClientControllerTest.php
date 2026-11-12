<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\DocumentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\SimpleExcel\SimpleExcelReader;
use Tests\TestCase;

class ClientControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'name_ar' => 'عبدالله القحطاني',
            'name_en' => 'Abdullah Al-Qahtani',
            'national_id' => '1012345678',
            'phone' => '0551234567',
            'branch_id' => $this->branch('RYD')->id,
            'is_active' => '1',
            ...$overrides,
        ];
    }

    public function test_creates_a_client_and_audits_without_the_national_id(): void
    {
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->post(route('admin.clients.store'), $this->payload())->assertRedirect();

        $client = Client::query()->sole();
        $this->assertSame('1012345678', $client->national_id);
        $this->assertSame(0, $client->visa_balance);
        $this->assertDatabaseHas(AuditLog::class, ['action_key' => 'client.created', 'target_id' => $client->id]);
        $this->assertStringNotContainsString('1012345678', (string) json_encode(AuditLog::query()->pluck('new_values')));
    }

    public function test_duplicate_national_id_is_rejected(): void
    {
        $admin = $this->userWithRole('super_admin');
        Client::factory()->create(['national_id' => '1012345678', 'branch_id' => $this->branch('QSM')->id]);

        $this->actingAs($admin)->post(route('admin.clients.store'), $this->payload(['national_id' => ' 10123 45678 ']))
            ->assertSessionHasErrors(['national_id' => __('clients.validation.duplicate_national_id')]);
    }

    public function test_validates_national_id_and_phone_format(): void
    {
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->post(route('admin.clients.store'), $this->payload(['national_id' => '3012345678', 'phone' => '123']))
            ->assertSessionHasErrors(['national_id', 'phone']);
    }

    public function test_riyadh_user_gets_404_on_a_qassim_client(): void
    {
        $qassim = Client::factory()->create(['branch_id' => $this->branch('QSM')->id]);
        $riyadh = $this->userWithRole('branch_manager', ['RYD']);

        $this->actingAs($riyadh)->get(route('admin.clients.show', $qassim))->assertNotFound();
        $this->actingAs($riyadh)->put(route('admin.clients.update', $qassim), $this->payload())->assertNotFound();
        $this->actingAs($riyadh)->post(route('admin.clients.store'), $this->payload(['branch_id' => $this->branch('QSM')->id]))
            ->assertSessionHasErrors('branch_id');
    }

    public function test_search_by_name_phone_and_exact_national_id(): void
    {
        $admin = $this->userWithRole('super_admin');
        Client::factory()->create(['name_en' => 'First Client', 'national_id' => '1000000001', 'phone' => '0500000001', 'branch_id' => $this->branch('RYD')->id]);
        Client::factory()->create(['name_en' => 'Second Client', 'national_id' => '1000000002', 'phone' => '0500000002', 'branch_id' => $this->branch('RYD')->id]);

        $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.clients.index', ['national_id' => '1000000002']))
            ->assertSee('Second Client')->assertDontSee('First Client');
        $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.clients.index', ['search' => '0500000001']))
            ->assertSee('First Client')->assertDontSee('Second Client');
    }

    public function test_national_id_is_masked_in_list_profile_and_export_without_permission(): void
    {
        $client = Client::factory()->create(['national_id' => '1012345698', 'branch_id' => $this->branch('RYD')->id]);
        $user = $this->userWithRole('rental_team', ['RYD']);
        $user->givePermissionTo('clients.export');

        $this->actingAs($user)->get(route('admin.clients.index'))->assertSee('10******98')->assertDontSee('1012345698');
        $this->actingAs($user)->get(route('admin.clients.show', $client))->assertSee('10******98')->assertDontSee('1012345698');

        $export = $this->actingAs($user)->get(route('admin.clients.export'));
        $row = SimpleExcelReader::create($export->baseResponse->getFile()->getPathname())->getRows()->first();
        $this->assertSame('10******98', $row[__('clients.fields.national_id')]);

        $user->givePermissionTo('clients.view_sensitive');
        $this->actingAs($user)->get(route('admin.clients.show', $client))->assertSee('1012345698');
    }

    public function test_documents_tab_shows_only_client_document_types(): void
    {
        $admin = $this->userWithRole('super_admin');
        $client = Client::factory()->create(['branch_id' => $this->branch('RYD')->id]);
        DocumentType::factory()->create(['name_en' => 'Family card', 'applies_to' => 'client']);
        DocumentType::factory()->create(['name_en' => 'Worker passport', 'applies_to' => 'worker']);

        $this->actingAs($admin)->withSession(['locale' => 'en'])
            ->get(route('admin.clients.show', [$client, 'tab' => 'documents']))
            ->assertOk()
            ->assertSee('Family card')
            ->assertDontSee('Worker passport');
    }

    public function test_client_document_types_are_managed_in_their_own_tab(): void
    {
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->post(route('admin.document-types.store'), [
            'applies_to' => 'client', 'country_id' => null, 'name_ar' => 'كرت العائلة', 'name_en' => 'Family card', 'is_active' => '1',
        ])->assertRedirect(route('admin.document-types.index', ['subject' => 'client']));

        $type = DocumentType::query()->sole();
        $this->assertSame('client', $type->applies_to);
        $this->assertNull($type->country_id);

        $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.document-types.index', ['subject' => 'client']))->assertSee('Family card');
        $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.document-types.index', ['subject' => 'worker']))->assertDontSee('Family card');
    }
}
