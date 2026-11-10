<?php

use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Http\Middleware\EnsureTwoFactorVerified;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureUserType;
use App\Http\Middleware\SessionTimeout;
use App\Http\Middleware\SetLocale;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
        ]);

        $middleware->alias([
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
            'active' => EnsureUserIsActive::class,
            'user.type' => EnsureUserType::class,
            'password.changed' => EnsurePasswordIsChanged::class,
            'two-factor' => EnsureTwoFactorVerified::class,
            'session.timeout' => SessionTimeout::class,
        ]);

        /*
         * Every authenticated area: active account, inactivity timeout,
         * 2FA confirmed and no forced password change pending.
         */
        $middleware->group('panel', [
            'auth',
            'active',
            'session.timeout',
            'two-factor',
            'password.changed',
            CheckMaintenanceMode::class,
        ]);

        $middleware->redirectUsersTo(fn (Request $request): string => $request->user() instanceof User
            ? route($request->user()->user_type->homeRoute())
            : '/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
