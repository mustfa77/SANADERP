<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAppearanceSettingsRequest;
use App\Http\Requests\Admin\UpdateGeneralSettingsRequest;
use App\Http\Requests\Admin\UpdateMailSettingsRequest;
use App\Http\Requests\Admin\UpdateMaintenanceSettingsRequest;
use App\Http\Requests\Admin\UpdateSecuritySettingsRequest;
use App\Models\Setting;
use App\Notifications\TestMail;
use App\Services\AuditLogger;
use App\Services\BackupService;
use App\Services\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

/**
 * System settings: site (name, logos), mail (SMTP) and security.
 */
class SettingController extends Controller
{
    private const SECRET_MASK = '********';

    public function __construct(private AuditLogger $audit, private SiteSettings $site, private BackupService $backups) {}

    public function edit(): View
    {
        return view('admin.settings.edit', [
            'security' => collect(Setting::SECURITY)->map(fn (array $definition, string $key): int => Setting::integer($key)),
            'securityDefinitions' => Setting::SECURITY,
            'siteNames' => ['site_name_ar' => $this->site->brand('ar'), 'site_name_en' => $this->site->brand('en')],
            'appearance' => ['primary_color' => $this->site->primaryColor()]
                + collect(SiteSettings::APPEARANCE)->map(fn (array $definition, string $key): string => $this->site->appearance($key))->all(),
            'mail' => $this->site->mailValues(),
            'hasMailPassword' => $this->site->mailPassword() !== null,
            'maintenance' => [
                'maintenance_enabled' => $this->site->maintenanceEnabled(),
                'maintenance_message_ar' => Setting::get('maintenance_message_ar'),
                'maintenance_message_en' => Setting::get('maintenance_message_en'),
                'backup_auto_enabled' => Setting::boolean('backup_auto_enabled'),
            ] + collect(Setting::MAINTENANCE)->map(fn (array $definition, string $key): int => Setting::integer($key))->all(),
            'maintenanceDefinitions' => Setting::MAINTENANCE,
            'backups' => $this->backups->all(),
        ]);
    }

    public function updateGeneral(UpdateGeneralSettingsRequest $request): RedirectResponse
    {
        $changes = $this->saveValues($request->only(['site_name_ar', 'site_name_en']));

        foreach (array_keys(SiteSettings::IMAGES) as $key) {
            $current = Setting::get($key);
            $replacement = null;

            if ($request->hasFile($key)) {
                $replacement = $request->file($key)->store('branding', 'public');
            } elseif (! $request->boolean('remove_'.$key) || ! $current) {
                continue;
            }

            if ($current) {
                Storage::disk('public')->delete($current);
            }

            Setting::put($key, $replacement);
            $changes['old'][$key] = $current ? basename($current) : null;
            $changes['new'][$key] = $replacement ? basename($replacement) : null;
        }

        $this->audited($changes, 'general');

        return redirect()->route('admin.settings.edit', ['tab' => 'general'])->with('success', __('settings.flash.updated'));
    }

    public function updateAppearance(UpdateAppearanceSettingsRequest $request): RedirectResponse
    {
        $values = $request->validated();
        $values['primary_color'] = strtolower($values['primary_color']);

        $this->audited($this->saveValues($values), 'appearance');

        return redirect()->route('admin.settings.edit', ['tab' => 'appearance'])->with('success', __('settings.flash.updated'));
    }

    public function resetAppearance(): RedirectResponse
    {
        $defaults = ['primary_color' => SiteSettings::DEFAULT_PRIMARY_COLOR]
            + array_map(fn (array $definition): string => $definition['default'], SiteSettings::APPEARANCE);

        $this->audited($this->saveValues($defaults), 'appearance');

        return redirect()->route('admin.settings.edit', ['tab' => 'appearance'])->with('success', __('settings.appearance.reset_done'));
    }

    public function updateMail(UpdateMailSettingsRequest $request): RedirectResponse
    {
        $values = $request->safe()->except('mail_password');
        $values['mail_port'] = $request->filled('mail_port') ? (string) $request->integer('mail_port') : null;
        $changes = $this->saveValues($values);

        if ($request->filled('mail_password')) {
            Setting::put('mail_password', Crypt::encryptString($request->string('mail_password')));
            $changes['old']['mail_password'] = self::SECRET_MASK;
            $changes['new']['mail_password'] = self::SECRET_MASK;
        }

        $this->audited($changes, 'mail');

        return redirect()->route('admin.settings.edit', ['tab' => 'mail'])->with('success', __('settings.flash.updated'));
    }

    public function sendTestMail(Request $request): RedirectResponse
    {
        $redirect = redirect()->route('admin.settings.edit', ['tab' => 'mail']);

        try {
            $request->user()->notifyNow(new TestMail);
        } catch (Throwable $exception) {
            Log::warning('Test mail failed', ['error' => $exception->getMessage()]);

            return $redirect->with('error', __('settings.mail.test_failed', ['error' => $exception->getMessage()]));
        }

        return $redirect->with('success', __('settings.mail.test_sent', ['email' => $request->user()->email]));
    }

    public function updateMaintenance(UpdateMaintenanceSettingsRequest $request): RedirectResponse
    {
        $wasEnabled = $this->site->maintenanceEnabled();

        $values = $request->safe()->only(['maintenance_message_ar', 'maintenance_message_en']);
        $values['maintenance_enabled'] = $request->boolean('maintenance_enabled') ? '1' : '0';
        $values['backup_auto_enabled'] = $request->boolean('backup_auto_enabled') ? '1' : '0';

        foreach (array_keys(Setting::MAINTENANCE) as $key) {
            $values[$key] = (string) $request->integer($key);
        }

        $this->audited($this->saveValues($values), 'maintenance');

        if ($wasEnabled !== $request->boolean('maintenance_enabled')) {
            $this->audit->log($request->boolean('maintenance_enabled') ? 'maintenance.enabled' : 'maintenance.disabled');
        }

        return redirect()->route('admin.settings.edit', ['tab' => 'maintenance'])->with('success', __('settings.flash.updated'));
    }

    public function updateSecurity(UpdateSecuritySettingsRequest $request): RedirectResponse
    {
        $changes = ['old' => [], 'new' => []];

        foreach (array_keys(Setting::SECURITY) as $key) {
            $before = Setting::integer($key);
            $after = $request->integer($key);

            if ($before !== $after) {
                Setting::put($key, (string) $after);
                $changes['old'][$key] = $before;
                $changes['new'][$key] = $after;
            }
        }

        $this->audited($changes, 'security');

        return redirect()->route('admin.settings.edit', ['tab' => 'security'])->with('success', __('settings.flash.updated'));
    }

    /**
     * Save string values and return the ones that changed.
     *
     * @param  array<string, mixed>  $values
     * @return array{old: array<string, mixed>, new: array<string, mixed>}
     */
    private function saveValues(array $values): array
    {
        $changes = ['old' => [], 'new' => []];

        foreach ($values as $key => $value) {
            $before = Setting::get($key);
            $after = $value === null || $value === '' ? null : (string) $value;

            if ($before !== $after) {
                Setting::put($key, $after);
                $changes['old'][$key] = $before;
                $changes['new'][$key] = $after;
            }
        }

        return $changes;
    }

    /**
     * @param  array{old: array<string, mixed>, new: array<string, mixed>}  $changes
     */
    private function audited(array $changes, string $section): void
    {
        if ($changes['new'] === [] && $changes['old'] === []) {
            return;
        }

        $this->audit->log(
            'settings.updated',
            params: ['section' => ['ar' => __('settings.tabs.'.$section, [], 'ar'), 'en' => __('settings.tabs.'.$section, [], 'en')]],
            old: $changes['old'],
            new: $changes['new'],
        );
    }
}
