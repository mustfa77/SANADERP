<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Enums\SponsorType;
use App\Enums\WorkerStatus;
use App\Models\AuditLog;
use App\Models\Country;
use App\Models\DocumentType;
use App\Models\Profession;
use App\Models\Worker;
use App\Models\WorkerSalaryChange;
use App\Services\Workers\WorkerStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\SimpleExcel\SimpleExcelReader;
use Tests\TestCase;

class WorkerControllerTest extends TestCase
{
    use RefreshDatabase;

    private Country $country;

    private Profession $profession;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();
        $this->country = Country::factory()->create(['code' => 'PH']);
        $this->profession = Profession::factory()->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'name_en' => 'Maria Santos',
            'name_ar' => 'ماريا سانتوس',
            'gender' => 'female',
            'country_id' => $this->country->id,
            'profession_id' => $this->profession->id,
            'branch_id' => $this->branch('RYD')->id,
            'passport_no' => 'P0123456C',
            'passport_type' => 'ordinary',
            'salary' => 1500,
            'sponsor_type' => 'company',
            'rental_type' => 'none',
            ...$overrides,
        ];
    }

    private function worker(string $branch = 'RYD', array $attributes = []): Worker
    {
        return Worker::factory()->create([
            'branch_id' => $this->branch($branch)->id,
            'country_id' => $this->country->id,
            'profession_id' => $this->profession->id,
            ...$attributes,
        ]);
    }

    public function test_creates_a_worker_as_new_candidate_with_history_and_audit(): void
    {
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->post(route('admin.workers.store'), $this->payload())->assertRedirect();

        $worker = Worker::query()->sole();
        $this->assertSame(WorkerStatus::CandidateNew, $worker->status);
        $this->assertSame($admin->id, $worker->created_by);
        $this->assertNull($worker->statusHistory()->sole()->from_status);
        $this->assertDatabaseHas(AuditLog::class, ['action_key' => 'worker.created', 'target_id' => $worker->id]);

        // The passport number never reaches the audit log in clear.
        $this->assertStringNotContainsString('P0123456C', (string) json_encode(AuditLog::query()->pluck('new_values')));
    }

    public function test_duplicate_passport_is_rejected_whatever_the_case_and_spacing(): void
    {
        $admin = $this->userWithRole('super_admin');
        $existing = $this->worker('QSM', ['passport_no' => 'P0123456C']);

        $this->actingAs($admin)->from(route('admin.workers.create'))
            ->post(route('admin.workers.store'), $this->payload(['passport_no' => ' p012 3456c ']))
            ->assertSessionHasErrors(['passport_no' => __('workers.validation.duplicate_passport')]);

        $this->assertSame(1, Worker::query()->count());

        // The error links to the existing worker when the user may see it.
        $this->actingAs($admin)->from(route('admin.workers.create'))->followingRedirects()
            ->post(route('admin.workers.store'), $this->payload(['passport_no' => 'P0123456C']))
            ->assertSee(__('workers.validation.duplicate_passport'))
            ->assertSee(route('admin.workers.show', $existing));
    }

    public function test_duplicate_passport_link_is_hidden_when_the_worker_is_in_another_branch(): void
    {
        $existing = $this->worker('QSM', ['passport_no' => 'P0123456C']);
        $riyadh = $this->userWithRole('branch_manager', ['RYD']);

        $this->actingAs($riyadh)->from(route('admin.workers.create'))
            ->post(route('admin.workers.store'), $this->payload(['passport_no' => 'P0123456C']))
            ->assertSessionHasErrors('passport_no');

        $this->actingAs($riyadh)->from(route('admin.workers.create'))->followingRedirects()
            ->post(route('admin.workers.store'), $this->payload(['passport_no' => 'P0123456C']))
            ->assertSee(__('workers.validation.duplicate_passport'))
            ->assertDontSee(route('admin.workers.show', $existing));
    }

    public function test_duplicate_iqama_is_rejected(): void
    {
        $admin = $this->userWithRole('super_admin');
        $this->worker('RYD', ['iqama_no' => '2123456789']);

        $this->actingAs($admin)
            ->post(route('admin.workers.store'), $this->payload(['passport_no' => 'Z9999999', 'iqama_no' => '21234 56789']))
            ->assertSessionHasErrors(['iqama_no' => __('workers.validation.duplicate_iqama')]);
    }

    public function test_status_is_ignored_in_the_form(): void
    {
        $admin = $this->userWithRole('super_admin');
        $worker = $this->worker();

        $this->actingAs($admin)->put(route('admin.workers.update', $worker), [
            ...$this->payload(['passport_no' => $worker->passport_no]), 'status' => 'absconded',
        ])->assertRedirect();

        $this->assertSame(WorkerStatus::CandidateNew, $worker->fresh()->status);
    }

    public function test_salary_change_requires_a_reason_and_is_recorded(): void
    {
        $admin = $this->userWithRole('super_admin');
        $worker = $this->worker('RYD', ['salary' => 1500]);
        $base = $this->payload(['passport_no' => $worker->passport_no, 'salary' => 1800]);

        $this->actingAs($admin)->put(route('admin.workers.update', $worker), $base)
            ->assertSessionHasErrors(['salary_change_reason' => __('workers.validation.salary_reason_required')]);
        $this->assertSame('1500.00', $worker->fresh()->salary);

        $this->actingAs($admin)->put(route('admin.workers.update', $worker), [...$base, 'salary_change_reason' => 'Annual raise'])->assertRedirect();

        $change = WorkerSalaryChange::query()->sole();
        $this->assertSame('1500.00', $change->old_salary);
        $this->assertSame('1800.00', $change->new_salary);
        $this->assertSame('Annual raise', $change->reason);
        $this->assertSame($admin->id, $change->changed_by);
    }

    public function test_riyadh_user_gets_404_on_a_qassim_worker(): void
    {
        $qassimWorker = $this->worker('QSM');
        $riyadh = $this->userWithRole('branch_manager', ['RYD']);

        $this->actingAs($riyadh)->get(route('admin.workers.show', $qassimWorker))->assertNotFound();
        $this->actingAs($riyadh)->get(route('admin.workers.edit', $qassimWorker))->assertNotFound();
        $this->actingAs($riyadh)->post(route('admin.workers.status', $qassimWorker), ['status' => 'sick', 'reason' => 'x'])->assertNotFound();
        $this->actingAs($riyadh)->get(route('admin.workers.show', $this->worker('RYD')))->assertOk();
    }

    public function test_passport_is_masked_in_list_profile_and_export_without_permission(): void
    {
        $worker = $this->worker('RYD', ['passport_no' => 'P0123456C', 'iqama_no' => '2123456789']);
        $user = $this->userWithRole('rental_team', ['RYD']);
        $user->givePermissionTo(['workers.view', 'workers.export']);

        $this->actingAs($user)->get(route('admin.workers.index'))->assertSee('P01****6C')->assertDontSee('P0123456C');
        $this->actingAs($user)->get(route('admin.workers.show', $worker))
            ->assertSee('P01****6C')->assertDontSee('P0123456C')
            ->assertSee('21******89')->assertDontSee('2123456789');

        $export = $this->actingAs($user)->get(route('admin.workers.export'));
        $row = SimpleExcelReader::create($export->baseResponse->getFile()->getPathname())->getRows()->first();
        $this->assertSame('P01****6C', $row[__('workers.fields.passport_no')]);

        $user->givePermissionTo('workers.view_sensitive');
        $this->actingAs($user)->get(route('admin.workers.index'))->assertSee('P0123456C');
    }

    public function test_exact_passport_search_uses_the_lookup(): void
    {
        $admin = $this->userWithRole('super_admin');
        $match = $this->worker('RYD', ['passport_no' => 'P0123456C', 'name_en' => 'Matching Worker']);
        $this->worker('RYD', ['passport_no' => 'P0123457C', 'name_en' => 'Other Worker']);

        $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.workers.index', ['passport' => 'p0123456c']))
            ->assertSee('Matching Worker')
            ->assertDontSee('Other Worker');
    }

    public function test_documents_tab_shows_the_checklist_for_the_workers_country_and_profession(): void
    {
        $admin = $this->userWithRole('super_admin');
        $worker = $this->worker();
        DocumentType::factory()->create(['name_en' => 'Global passport copy']);
        DocumentType::factory()->create(['name_en' => 'Philippine OEC', 'country_id' => $this->country->id]);
        DocumentType::factory()->create(['name_en' => 'Kenya only', 'country_id' => Country::factory()->create()->id]);
        DocumentType::factory()->create(['name_en' => 'Driver licence', 'profession_id' => Profession::factory()->create()->id]);
        DocumentType::factory()->create(['name_en' => 'Client ID copy', 'applies_to' => 'client']);

        $this->actingAs($admin)->withSession(['locale' => 'en'])
            ->get(route('admin.workers.show', [$worker, 'tab' => 'documents']))
            ->assertOk()
            ->assertSee('Global passport copy')
            ->assertSee('Philippine OEC')
            ->assertDontSee('Kenya only')
            ->assertDontSee('Driver licence')
            ->assertDontSee('Client ID copy');
    }

    public function test_manual_status_ui_shows_only_allowed_manual_transitions(): void
    {
        $admin = $this->userWithRole('super_admin');
        $worker = $this->worker('RYD', ['sponsor_type' => SponsorType::Company]);
        app(WorkerStatusService::class)->initialize($worker, WorkerStatus::Available);

        $page = $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.workers.show', $worker))->assertOk();
        $page->assertSee('value="sick"', false)
            ->assertSee('value="awaiting_decision"', false)
            ->assertDontSee('value="rented_monthly"', false)
            ->assertDontSee('value="absconded"', false);

        $this->actingAs($admin)->post(route('admin.workers.status', $worker), ['status' => 'rented_monthly', 'reason' => 'Try'])
            ->assertSessionHasErrors('status');
        $this->actingAs($admin)->post(route('admin.workers.status', $worker), ['status' => 'sick'])
            ->assertSessionHasErrors('reason');

        $this->actingAs($admin)->post(route('admin.workers.status', $worker), ['status' => 'sick', 'reason' => 'Hospital visit'])->assertRedirect();
        $this->assertSame(WorkerStatus::Sick, $worker->fresh()->status);
    }

    public function test_candidate_has_no_manual_status_button(): void
    {
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->get(route('admin.workers.show', $this->worker()))
            ->assertDontSee('change-status-modal');
    }

    public function test_change_status_needs_permission(): void
    {
        $worker = $this->worker('RYD', ['sponsor_type' => SponsorType::Company]);
        app(WorkerStatusService::class)->initialize($worker, WorkerStatus::Available);
        $user = $this->userWithRole('rental_team', ['RYD']);
        $user->givePermissionTo('workers.view');

        $this->actingAs($user)->get(route('admin.workers.show', $worker))->assertDontSee('change-status-modal');
        $this->actingAs($user)->post(route('admin.workers.status', $worker), ['status' => 'sick', 'reason' => 'x'])->assertForbidden();
    }

    public function test_profile_tabs_come_from_config_and_respect_permissions(): void
    {
        $worker = $this->worker();
        $user = $this->userWithRole('rental_team', ['RYD']);
        $user->givePermissionTo('workers.view');

        $this->actingAs($user)->get(route('admin.workers.show', $worker))
            ->assertSee(__('workers.tabs.overview'))
            ->assertSee(__('workers.tabs.status_history'))
            ->assertDontSee('id="tab-documents"', false);

        config(['profile_tabs.worker' => [
            ['key' => 'status_history', 'label' => 'workers.tabs.status_history', 'view' => 'admin.workers.tabs.status-history', 'permission' => 'workers.view', 'icon' => 'ri-history-line', 'sort' => 1],
        ]]);

        $this->actingAs($user)->get(route('admin.workers.show', $worker))
            ->assertSee('id="tab-status_history"', false)
            ->assertDontSee('id="tab-overview"', false);
    }

    public function test_worker_photo_is_served_only_through_the_protected_route(): void
    {
        Storage::fake('local');
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->post(route('admin.workers.store'), [
            ...$this->payload(), 'photo' => UploadedFile::fake()->image('maria.jpg'),
        ]);
        $worker = Worker::query()->sole();

        Storage::disk('local')->assertExists($worker->photo_path);
        $this->actingAs($admin)->get(route('admin.workers.photo', $worker))->assertOk();
        // The accounts role has no workers.view permission.
        $this->actingAs($this->userWithRole('accounts', ['RYD']))->get(route('admin.workers.photo', $worker))->assertForbidden();
    }
}
