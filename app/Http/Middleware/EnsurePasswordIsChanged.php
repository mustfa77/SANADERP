<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends users flagged with must_change_password to the change-password page
 * until they choose a new password.
 */
class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->must_change_password
            && ! $request->routeIs('password.force', 'password.force.update', 'logout', 'lang.switch')) {
            return redirect()->route('password.force');
        }

        return $next($request);
    }
}
