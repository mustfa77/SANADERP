<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Notifications\TestMail;
use App\Services\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();
    }

    /**
     * @return array<string, int>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'session_timeout_minutes' => 120,
            'login_max_attempts' => 5,
            'login_lockout_minutes' => 15,
            'two_factor_code_minutes' => 10,
            ...$overrides,
        ];
    }

    public function test_returns_403_without_settings_permission(): void
    {
        $manager = $this->userWithRole('general_manager');

        $this->actingAs($manager)->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($manager)->put(route('admin.settings.security'), $this->payload())->assertForbidden();
    }

    public function test_view_only_user_sees_values_without_save_button(): void
    {
        $manager = $this->userWithRole('general_manager');
        $manager->givePermissionTo('settings.view');

        $this->actingAs($manager)->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee(__('settings.fields.session_timeout_minutes.label'))
            ->assertDontSee(__('common.save'));

        $this->actingAs($manager)->put(route('admin.settings.security'), $this->payload())->assertForbidden();
    }

    public function test_updates_settings_and_audits_only_changed_values(): void
    {
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)
            ->put(route('admin.settings.security'), $this->payload(['session_timeout_minutes' => 30]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(30, Setting::integer('session_timeout_minutes'));

        $log = AuditLog::query()->where('action_key', 'settings.updated')->sole();
        $this->assertSame(['session_timeout_minutes' => 120], $log->old_values);
        $this->assertSame(['session_timeout_minutes' => 30], $log->new_values);
    }

    public function test_rejects_values_outside_allowed_range(): void
    {
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)
            ->put(route('admin.settings.security'), $this->payload(['login_max_attempts' => 1, 'session_timeout_minutes' => 0]))
            ->assertSessionHasErrors(['login_max_attempts', 'session_timeout_minutes']);

        $this->assertSame(5, Setting::integer('login_max_attempts'));
    }

    public function test_updates_site_name_and_logo_used_by_the_layout(): void
    {
        Storage::fake('public');
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->put(route('admin.settings.general'), [
            'site_name_ar' => 'مؤسسة سند',
            'site_name_en' => 'Sanad Est.',
            'logo_dark' => UploadedFile::fake()->image('logo.png', 200, 40),
        ])->assertRedirect(route('admin.settings.edit', ['tab' => 'general']));

        $path = Setting::get('logo_dark');
        Storage::disk('public')->assertExists($path);

        $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.dashboard'))
            ->assertSee('Sanad Est.')
            ->assertSee(Storage::disk('public')->url($path));

        $this->actingAs($admin)->put(route('admin.settings.general'), [
            'site_name_ar' => 'مؤسسة سند',
            'site_name_en' => 'Sanad Est.',
            'remove_logo_dark' => '1',
        ]);

        Storage::disk('public')->assertMissing($path);
        $this->assertNull(Setting::get('logo_dark'));
    }

    public function test_updates_appearance_and_applies_it_to_the_layout(): void
    {
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->put(route('admin.settings.appearance'), [
            'primary_color' => '#3B5DE7',
            'theme_mode' => 'dark',
            'sidebar_color' => 'light',
            'topbar_color' => 'dark',
            'sidebar_size' => 'compact',
            'layout_width' => 'boxed',
            'button_shape' => 'rounded',
        ])->assertRedirect(route('admin.settings.edit', ['tab' => 'appearance']));

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertSee('--bs-primary:#3b5de7', false)
            ->assertSee('--bs-btn-border-radius:50rem', false)
            ->assertSee('data-bs-theme="dark"', false)
            ->assertSee('data-topbar="dark"', false)
            ->assertSee('data-sidebar-size="small"', false)
            ->assertSee('data-layout-size="boxed"', false)
            ->assertDontSee('data-sidebar="dark"', false);

        $this->assertDatabaseHas(AuditLog::class, ['action_key' => 'settings.updated']);

        $this->actingAs($admin)->delete(route('admin.settings.appearance.reset'));

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertDontSee('id="theme-settings"', false)
            ->assertSee('data-sidebar="dark"', false);
    }

    public function test_rejects_color_that_could_inject_css(): void
    {
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->put(route('admin.settings.appearance'), [
            'primary_color' => 'red;}</style><script>alert(1)</script>',
            'theme_mode' => 'light',
            'sidebar_color' => 'dark',
            'topbar_color' => 'light',
            'sidebar_size' => 'default',
            'layout_width' => 'fluid',
            'button_shape' => 'default',
        ])->assertSessionHasErrors('primary_color');

        $this->assertNull(Setting::get('primary_color'));
    }

    public function test_rejects_svg_logo(): void
    {
        Storage::fake('public');
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->put(route('admin.settings.general'), [
            'site_name_ar' => 'سند',
            'site_name_en' => 'Sanad',
            'logo_dark' => UploadedFile::fake()->create('logo.svg', 5, 'image/svg+xml'),
        ])->assertSessionHasErrors('logo_dark');
    }

    public function test_saves_mail_settings_with_encrypted_password_and_masked_audit(): void
    {
        $admin = $this->userWithRole('super_admin');
        $mail = [
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.example.com',
            'mail_port' => '587',
            'mail_encryption' => 'tls',
            'mail_username' => 'erp@example.com',
            'mail_password' => 'Secret-Smtp-1',
            'mail_from_address' => 'erp@example.com',
            'mail_from_name' => 'Sanad',
        ];

        $this->actingAs($admin)->put(route('admin.settings.mail'), $mail)->assertSessionHasNoErrors();

        $this->assertNotSame('Secret-Smtp-1', Setting::get('mail_password'));
        $this->assertSame('Secret-Smtp-1', app(SiteSettings::class)->mailPassword());

        $log = AuditLog::query()->where('action_key', 'settings.updated')->sole();
        $this->assertSame('********', $log->new_values['mail_password']);
        $this->assertStringNotContainsString('Secret-Smtp-1', json_encode($log->new_values));

        // An empty password keeps the saved one.
        $this->actingAs($admin)->put(route('admin.settings.mail'), [...$mail, 'mail_password' => '', 'mail_port' => '465', 'mail_encryption' => 'ssl']);
        $this->assertSame('Secret-Smtp-1', app(SiteSettings::class)->mailPassword());

        app(SiteSettings::class)->applyMailConfig();
        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
        $this->assertSame(465, config('mail.mailers.smtp.port'));
        $this->assertSame('Secret-Smtp-1', config('mail.mailers.smtp.password'));
    }

    public function test_smtp_requires_host_and_port(): void
    {
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->put(route('admin.settings.mail'), [
            'mail_mailer' => 'smtp',
            'mail_encryption' => 'tls',
            'mail_from_address' => 'erp@example.com',
        ])->assertSessionHasErrors(['mail_host', 'mail_port']);
    }

    public function test_sends_test_mail_to_current_user(): void
    {
        Notification::fake();
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->post(route('admin.settings.mail.test'))
            ->assertSessionHas('success', __('settings.mail.test_sent', ['email' => $admin->email]));

        Notification::assertSentTo($admin, TestMail::class);
    }

    public function test_login_lockout_follows_saved_settings(): void
    {
        Setting::put('login_max_attempts', '3');
        Setting::put('login_lockout_minutes', '2');
        $user = $this->userWithRole('rental_team');

        foreach (range(1, 3) as $attempt) {
            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong']);
        }

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Password1'])
            ->assertSessionHasErrors(['email' => __('auth.throttle', ['seconds' => 120, 'minutes' => 2])]);
        $this->assertGuest();
    }
}
