<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks the app until the emailed 2FA code is confirmed for this session.
 */
class EnsureTwoFactorVerified
{
    public const SESSION_KEY = 'two_factor_pending';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get(self::SESSION_KEY)
            && ! $request->routeIs('two-factor.*', 'logout', 'lang.switch')) {
            return redirect()->route('two-factor.show');
        }

        return $next($request);
    }
}
