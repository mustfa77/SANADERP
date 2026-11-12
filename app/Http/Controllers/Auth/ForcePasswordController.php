<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Page shown when an admin forced a password change on next login.
 */
class ForcePasswordController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function edit(Request $request): View|RedirectResponse
    {
        if (! $request->user()->must_change_password) {
            return redirect()->route($request->user()->user_type->homeRoute());
        }

        return view('auth.force-password');
    }

    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->forceFill([
            'password' => $request->string('password'),
            'must_change_password' => false,
        ])->save();

        $this->audit->log('auth.password_changed', $user, ['name' => $user->translations('name')]);

        return redirect()->route($user->user_type->homeRoute())->with('success', __('auth.password_changed'));
    }
}
