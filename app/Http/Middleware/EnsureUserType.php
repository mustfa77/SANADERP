<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route group to user types, e.g. "user.type:internal" or
 * "user.type:external_agency".
 */
class EnsureUserType
{
    public function handle(Request $request, Closure $next, string ...$types): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User && in_array($user->user_type->value, $types, true), 403);

        return $next($request);
    }
}
