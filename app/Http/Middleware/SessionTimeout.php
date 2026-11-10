<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs the user out after the inactivity period set in settings
 * (session_timeout_minutes).
 */
class SessionTimeout
{
    private const SESSION_KEY = 'last_activity_at';

    public function __construct(private AuditLogger $audit) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $timeout = Setting::integer('session_timeout_minutes');
        $lastActivity = (int) $request->session()->get(self::SESSION_KEY, time());

        if ($timeout > 0 && time() - $lastActivity > $timeout * 60) {
            $this->audit->log('auth.timeout', Auth::user());

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => __('auth.timeout')]);
        }

        $request->session()->put(self::SESSION_KEY, time());

        return $next($request);
    }
}
