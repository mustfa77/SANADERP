<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureTwoFactorVerified;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LoginAttempt;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\TwoFactorCode;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * @throws ValidationException
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $key = $request->throttleKey();
        $email = $request->string('email')->lower()->toString();
        $user = User::query()->where('email', $email)->first();
        $maxAttempts = Setting::integer('login_max_attempts');

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $this->recordAttempt($request, $email, $user, 'locked_out');

            throw $this->lockoutException($key);
        }

        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            RateLimiter::hit($key, Setting::integer('login_lockout_minutes') * 60);
            $this->recordAttempt($request, $email, $user, 'invalid_credentials');
            $this->audit->log('auth.failed', $user, ['email' => $email], actor: $user);

            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                $this->audit->log('auth.lockout', $user, ['email' => $email], actor: $user);

                throw $this->lockoutException($key);
            }

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        if (! $user->isActive()) {
            $this->recordAttempt($request, $email, $user, 'inactive');
            $this->audit->log('auth.failed', $user, ['email' => $email], actor: $user);

            throw ValidationException::withMessages(['email' => __('auth.inactive')]);
        }

        RateLimiter::clear($key);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->put('locale', $user->preferred_language);

        $user->forceFill(['last_login_at' => now()])->save();
        $this->recordAttempt($request, $email, $user, null);
        $this->audit->log('auth.login', $user, ['name' => $user->translations('name')]);

        if ($user->requiresTwoFactor()) {
            $request->session()->put(EnsureTwoFactorVerified::SESSION_KEY, true);
            $user->notify(new TwoFactorCode(TwoFactorCode::issue($user)));

            return redirect()->route('two-factor.show');
        }

        return redirect()->intended(route($user->user_type->homeRoute()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        if ($user = $request->user()) {
            $this->audit->log('auth.logout', $user, ['name' => $user->translations('name')]);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function recordAttempt(Request $request, string $email, ?User $user, ?string $failureReason): void
    {
        LoginAttempt::query()->create([
            'user_id' => $user?->getKey(),
            'email' => $email,
            'successful' => $failureReason === null,
            'failure_reason' => $failureReason,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);
    }

    private function lockoutException(string $key): ValidationException
    {
        $seconds = RateLimiter::availableIn($key);

        return ValidationException::withMessages([
            'email' => __('auth.throttle', ['seconds' => $seconds, 'minutes' => (int) ceil($seconds / 60)]),
        ]);
    }
}
