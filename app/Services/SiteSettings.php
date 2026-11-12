<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Site branding (name, logos) and outgoing mail settings stored in the
 * settings table. Missing values fall back to the defaults / .env.
 */
class SiteSettings
{
    /**
     * Uploadable images and their default file under public/.
     *
     * @var array<string, string>
     */
    public const IMAGES = [
        'logo_dark' => 'assets/images/logo-dark.png',
        'logo_light' => 'assets/images/logo-light.png',
        'logo_sm' => 'assets/images/logo-sm.png',
        'favicon' => 'assets/images/favicon.ico',
    ];

    /**
     * Mail setting keys (mail_password is stored encrypted).
     *
     * @var list<string>
     */
    public const MAIL_KEYS = [
        'mail_mailer', 'mail_host', 'mail_port', 'mail_encryption',
        'mail_username', 'mail_password', 'mail_from_address', 'mail_from_name',
    ];

    /**
     * Appearance options (template body attributes) and their default value.
     *
     * @var array<string, array{default: string, options: list<string>}>
     */
    public const APPEARANCE = [
        'theme_mode' => ['default' => 'light', 'options' => ['light', 'dark']],
        'sidebar_color' => ['default' => 'dark', 'options' => ['dark', 'light']],
        'topbar_color' => ['default' => 'light', 'options' => ['light', 'dark']],
        'sidebar_size' => ['default' => 'default', 'options' => ['default', 'compact', 'icon']],
        'layout_width' => ['default' => 'fluid', 'options' => ['fluid', 'boxed']],
        'button_shape' => ['default' => 'default', 'options' => ['default', 'rounded', 'square']],
    ];

    public const DEFAULT_PRIMARY_COLOR = '#0bb197';

    public const MAILERS = ['smtp', 'log'];

    public const ENCRYPTIONS = ['none', 'tls', 'ssl'];

    /**
     * Site name in the current locale, falling back to the other language
     * and then to the lang file.
     */
    public function brand(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $other = $locale === 'ar' ? 'en' : 'ar';

        return Setting::get('site_name_'.$locale)
            ?: Setting::get('site_name_'.$other)
            ?: __('app.brand', [], $locale);
    }

    public function imageUrl(string $key): string
    {
        $path = Setting::get($key);

        return $path ? Storage::disk('public')->url($path) : asset(self::IMAGES[$key]);
    }

    public function hasCustomImage(string $key): bool
    {
        return (bool) Setting::get($key);
    }

    public function appearance(string $key): string
    {
        $value = Setting::get($key);

        return in_array($value, self::APPEARANCE[$key]['options'], true) ? $value : self::APPEARANCE[$key]['default'];
    }

    public function primaryColor(): string
    {
        $color = Setting::get('primary_color');

        return is_string($color) && preg_match('/^#[0-9a-f]{6}$/i', $color) ? strtolower($color) : self::DEFAULT_PRIMARY_COLOR;
    }

    /**
     * Attributes for the panel <body> tag (sidebar, topbar, size, width, mode).
     *
     * @return array<string, string>
     */
    public function bodyAttributes(): array
    {
        $attributes = ['data-bs-theme' => $this->appearance('theme_mode'), 'data-topbar' => $this->appearance('topbar_color')];

        if ($this->appearance('sidebar_color') === 'dark') {
            $attributes['data-sidebar'] = 'dark';
        }

        match ($this->appearance('sidebar_size')) {
            'compact' => $attributes['data-sidebar-size'] = 'small',
            'icon' => $attributes += ['class' => 'vertical-collpsed', 'data-keep-enlarged' => 'true'],
            default => null,
        };

        if ($this->appearance('layout_width') === 'boxed') {
            $attributes['data-layout-size'] = 'boxed';
        }

        return $attributes;
    }

    /**
     * CSS overriding the template's primary color and button shape.
     * Empty when both are on their defaults.
     */
    public function themeCss(): string
    {
        $css = '';
        $primary = $this->primaryColor();

        if ($primary !== self::DEFAULT_PRIMARY_COLOR) {
            $rgb = implode(',', $this->rgb($primary));
            $hover = $this->shade($primary, 0.15);
            $active = $this->shade($primary, 0.2);

            $css .= ":root,[data-bs-theme]{--bs-primary:{$primary};--bs-primary-rgb:{$rgb};--bs-link-color:{$primary};--bs-link-color-rgb:{$rgb};--bs-link-hover-color:{$hover};"
                ."--bs-sidebar-menu-item-active-color:{$primary};--bs-menu-item-active-color:{$primary};--bs-primary-text-emphasis:{$hover};--bs-primary-bg-subtle:rgba({$rgb},.12);--bs-primary-border-subtle:rgba({$rgb},.35)}"
                .".btn-primary{--bs-btn-bg:{$primary};--bs-btn-border-color:{$primary};--bs-btn-hover-bg:{$hover};--bs-btn-hover-border-color:{$hover};--bs-btn-active-bg:{$active};--bs-btn-active-border-color:{$active};--bs-btn-disabled-bg:{$primary};--bs-btn-disabled-border-color:{$primary};--bs-btn-focus-shadow-rgb:{$rgb}}"
                .".btn-primary:active,.btn-primary:focus,.btn-primary:hover{background-color:{$hover}!important;border-color:{$hover}!important}"
                .".btn-outline-primary{--bs-btn-color:{$primary};--bs-btn-border-color:{$primary};--bs-btn-hover-bg:{$primary};--bs-btn-hover-border-color:{$primary};--bs-btn-active-bg:{$primary};--bs-btn-active-border-color:{$primary};--bs-btn-disabled-color:{$primary};--bs-btn-disabled-border-color:{$primary};--bs-btn-focus-shadow-rgb:{$rgb}}"
                .".btn-subtle-primary,.badge-soft-primary{color:{$primary};background-color:rgba({$rgb},.12)}"
                .".btn-subtle-primary:active,.btn-subtle-primary:focus,.btn-subtle-primary:hover{color:#fff;background-color:{$primary}}"
                .".form-check-input:checked,.form-check-input[type=checkbox]:indeterminate{background-color:{$primary};border-color:{$primary}}"
                .".form-control:focus,.form-select:focus{border-color:rgba({$rgb},.5)}"
                .".nav-pills{--bs-nav-pills-link-active-bg:{$primary}}.pagination{--bs-pagination-active-bg:{$primary};--bs-pagination-active-border-color:{$primary}}"
                .".progress,.progress-stacked{--bs-progress-bar-bg:{$primary}}.list-group{--bs-list-group-active-bg:{$primary};--bs-list-group-active-border-color:{$primary}}"
                .".nav-tabs-custom .nav-item .nav-link.active{color:{$primary}}.nav-tabs-custom .nav-item .nav-link:after{background:{$primary}}"
                .".avatar-title{background-color:{$primary}}.vertical-collpsed .vertical-menu #sidebar-menu>ul>li:hover>a,.vertical-collpsed .vertical-menu #sidebar-menu>ul>li:hover>a i{color:{$primary}}"
                .'.select2-container--default .select2-results__option--highlighted[aria-selected]{background-color:'.$primary.'}';
        }

        $radius = match ($this->appearance('button_shape')) {
            'rounded' => '50rem',
            'square' => '0',
            default => null,
        };

        if ($radius !== null) {
            $css .= ".btn,.btn-sm,.btn-lg,.btn-group-sm>.btn,.btn-group-lg>.btn{--bs-btn-border-radius:{$radius}}";
        }

        return $css;
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function rgb(string $hex): array
    {
        return [hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2))];
    }

    /**
     * Darken a hex color by the given ratio (0..1).
     */
    private function shade(string $hex, float $ratio): string
    {
        return '#'.implode('', array_map(
            fn (int $channel): string => str_pad(dechex((int) round($channel * (1 - $ratio))), 2, '0', STR_PAD_LEFT),
            $this->rgb($hex),
        ));
    }

    public function maintenanceEnabled(): bool
    {
        return Setting::boolean('maintenance_enabled');
    }

    /**
     * Maintenance message in the current locale, falling back to the other
     * language and then to the default text.
     */
    public function maintenanceMessage(): string
    {
        $locale = app()->getLocale();
        $other = $locale === 'ar' ? 'en' : 'ar';

        return Setting::get('maintenance_message_'.$locale)
            ?: Setting::get('maintenance_message_'.$other)
            ?: __('errors.503.message');
    }

    public function mailPassword(): ?string
    {
        $encrypted = Setting::get('mail_password');

        if (! $encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Current mail values for the form (password never included).
     *
     * @return array<string, string|null>
     */
    public function mailValues(): array
    {
        $smtp = config('mail.mailers.smtp');

        return [
            'mail_mailer' => Setting::get('mail_mailer', config('mail.default')),
            'mail_host' => Setting::get('mail_host', $smtp['host'] ?? null),
            'mail_port' => Setting::get('mail_port', (string) ($smtp['port'] ?? '')),
            'mail_encryption' => Setting::get('mail_encryption', ($smtp['scheme'] ?? null) === 'smtps' ? 'ssl' : 'tls'),
            'mail_username' => Setting::get('mail_username', $smtp['username'] ?? null),
            'mail_from_address' => Setting::get('mail_from_address', config('mail.from.address')),
            'mail_from_name' => Setting::get('mail_from_name', config('mail.from.name')),
        ];
    }

    /**
     * Override the mail config with saved settings. Does nothing until mail
     * settings are saved, or while the database is not ready (install / migrate).
     */
    public function applyMailConfig(): void
    {
        try {
            $mailer = Setting::get('mail_mailer');
        } catch (Throwable) {
            return;
        }

        if (! $mailer) {
            return;
        }

        $encryption = Setting::get('mail_encryption', 'tls');

        config([
            'mail.default' => $mailer,
            'mail.mailers.smtp.host' => Setting::get('mail_host'),
            'mail.mailers.smtp.port' => (int) Setting::get('mail_port', 587),
            'mail.mailers.smtp.scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
            'mail.mailers.smtp.username' => Setting::get('mail_username'),
            'mail.mailers.smtp.password' => $this->mailPassword(),
            'mail.from.address' => Setting::get('mail_from_address', config('mail.from.address')),
            'mail.from.name' => Setting::get('mail_from_name') ?: config('mail.from.name'),
        ]);

        app('mail.manager')->forgetMailers();
    }
}
