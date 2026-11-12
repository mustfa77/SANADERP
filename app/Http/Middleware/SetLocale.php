<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Apply the locale chosen in the session, else the logged-in user's
     * preferred language, else config('app.locale').
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $locale = $request->session()->get('locale')
            ?? ($user instanceof User ? $user->preferred_language : null)
            ?? config('app.locale');

        if (array_key_exists($locale, config('app.available_locales'))) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
