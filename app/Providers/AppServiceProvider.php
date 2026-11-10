<?php

namespace App\Providers;

use App\Models\User;
use App\Services\SiteSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Abilities that keep their policy rules even for Super Admins
     * (self-protection, last Super Admin, system roles, Super Admin matrix).
     */
    public const GUARDED_ABILITIES = ['assignRoles', 'deactivate', 'delete', 'updatePermissions'];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SiteSettings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            if (in_array($ability, self::GUARDED_ABILITIES, true)) {
                return null;
            }

            return $user->isSuperAdmin() ? true : null;
        });

        Password::defaults(fn () => Password::min(8)->mixedCase()->numbers());

        Paginator::useBootstrapFive();

        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->session()->getId()));

        $siteSettings = $this->app->make(SiteSettings::class);
        View::share('site', $siteSettings);
        $siteSettings->applyMailConfig();
    }
}
