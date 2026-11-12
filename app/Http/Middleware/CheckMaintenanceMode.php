<?php

namespace App\Http\Middleware;

use App\Services\SiteSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * While maintenance mode is on (Settings > Backup & maintenance), only users
 * who can edit settings may use the panel. Everyone else sees the 503 page
 * with the maintenance message.
 */
class CheckMaintenanceMode
{
    public function __construct(private SiteSettings $site) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->site->maintenanceEnabled() && ! $request->user()?->can('settings.edit')) {
            return response()->view('errors.503', ['message' => $this->site->maintenanceMessage()], 503);
        }

        return $next($request);
    }
}
