<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\AuditLog;
use App\Models\LoginAttempt;
use App\Models\Setting;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

/**
 * Uses DatabaseMigrations: SQLite "VACUUM INTO" cannot run inside the
 * transaction RefreshDatabase wraps each test in.
 */
class BackupControllerTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seedAccess();
    }

    /**
     * @return array<string, mixed>
     */
    private function maintenancePayload(array $overrides = []): array
    {
        return [
            'maintenance_enabled' => '0',
            'maintenance_message_ar' => '',
            'maintenance_message_en' => '',
            'backup_auto_enabled' => '0',
            'backup_hour' => 2,
            'backup_keep' => 7,
            'audit_retention_days' => 0,
            'login_attempts_retention_days' => 90,
            ...$overrides,
        ];
    }

    public function test_requires_settings_edit_permission(): void
    {
        $manager = $this->userWithRole('general_manager');
        $manager->givePermissionTo('settings.view');

        $this->actingAs($manager)->post(route('admin.settings.backups.store'))->assertForbidden();
        $this->actingAs($manager)->put(route('admin.settings.maintenance'), $this->maintenancePayload())->assertForbidden();
    }

    public function test_creates_backup_zip_with_database_and_downloads_it(): void
    {
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->post(route('admin.settings.backups.store'))
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'maintenance']))
            ->assertSessionHas('success');

        $backup = app(BackupService::class)->all()->sole();
        $this->assertSame('manual', $backup['type']);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open(app(BackupService::class)->path($backup['name'])));
        $this->assertNotFalse($zip->locateName('database/database.sqlite'));
        $this->assertNotFalse($zip->locateName('manifest.json'));
        $zip->close();

        $this->assertDatabaseHas(AuditLog::class, ['action_key' => 'backup.created']);

        $this->actingAs($admin)->get(route('admin.settings.backups.download', $backup['name']))
            ->assertOk()
            ->assertDownload($backup['name']);

        $this->actingAs($admin)->get(route('admin.settings.edit', ['tab' => 'maintenance']))
            ->assertSee($backup['name']);

        $this->actingAs($admin)->delete(route('admin.settings.backups.destroy', $backup['name']))->assertRedirect();
        $this->assertCount(0, app(BackupService::class)->all());
    }

    public function test_rejects_file_names_outside_the_backup_folder(): void
    {
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->get('/admin/settings/backups/..%2F..%2F.env')->assertNotFound();
        $this->actingAs($admin)->get(route('admin.settings.backups.download', 'backup-manual-2026-01-01-000000.zip'))->assertNotFound();
    }

    public function test_keeps_only_the_configured_number_of_backups(): void
    {
        $service = app(BackupService::class);

        foreach (['01', '02', '03'] as $day) {
            Storage::disk('local')->put(BackupService::DIRECTORY."/backup-auto-2026-01-{$day}-020000.zip", 'zip');
        }

        $this->assertSame(1, $service->prune(2));
        $this->assertSame(
            ['backup-auto-2026-01-03-020000.zip', 'backup-auto-2026-01-02-020000.zip'],
            $service->all()->pluck('name')->all(),
        );
    }

    public function test_scheduled_backup_runs_only_when_enabled_and_due(): void
    {
        $this->travelTo(now()->setTime(2, 5));

        $this->artisan('backup:run', ['--scheduled' => true])->assertSuccessful();
        $this->assertCount(0, app(BackupService::class)->all());

        Setting::put('backup_auto_enabled', '1');
        Setting::put('backup_hour', '2');

        $this->artisan('backup:run', ['--scheduled' => true])->assertSuccessful();
        $this->artisan('backup:run', ['--scheduled' => true])->assertSuccessful();

        $this->assertSame(['auto'], app(BackupService::class)->all()->pluck('type')->all());
    }

    public function test_maintenance_mode_blocks_other_users_but_not_settings_editors(): void
    {
        $admin = $this->userWithRole('super_admin');
        $staff = $this->userWithRole('rental_team', ['RYD']);

        $this->actingAs($admin)->put(route('admin.settings.maintenance'), $this->maintenancePayload([
            'maintenance_enabled' => '1',
            'maintenance_message_ar' => 'صيانة حتى الساعة 10 مساءً',
        ]))->assertRedirect();

        $this->assertDatabaseHas(AuditLog::class, ['action_key' => 'maintenance.enabled']);

        $this->actingAs($staff)->get(route('admin.dashboard'))
            ->assertStatus(503)
            ->assertSee('صيانة حتى الساعة 10 مساءً');

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('settings.maintenance.active_banner'));

        $this->actingAs($admin)->put(route('admin.settings.maintenance'), $this->maintenancePayload());
        $this->actingAs($staff)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_prunes_records_older_than_retention(): void
    {
        Setting::put('audit_retention_days', '30');
        Setting::put('login_attempts_retention_days', '0');

        $old = AuditLog::query()->create(['action_key' => 'auth.login']);
        $old->forceFill(['created_at' => now()->subDays(31)])->save();
        $recent = AuditLog::query()->create(['action_key' => 'auth.login']);
        $attempt = LoginAttempt::query()->create(['email' => 'a@b.c', 'successful' => false]);
        $attempt->forceFill(['created_at' => now()->subYears(2)])->save();

        $this->artisan('model:prune', ['--model' => [AuditLog::class, LoginAttempt::class]])->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
        $this->assertModelExists($attempt);
    }
}
