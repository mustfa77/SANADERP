<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The logged-in user's own profile: password, language and 2FA.
 */
class ProfileController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function edit(Request $request): View
    {
        return view('admin.profile.edit', ['user' => $request->user()->load('roles')]);
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->forceFill(['password' => $request->string('password'), 'must_change_password' => false])->save();

        $this->audit->log('auth.password_changed', $user, ['name' => $user->translations('name')]);

        return back()->with('success', __('auth.password_changed'));
    }

    public function updateLanguage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'preferred_language' => ['required', Rule::in(array_keys(config('app.available_locales')))],
        ]);

        $request->user()->forceFill($validated)->save();
        $request->session()->put('locale', $validated['preferred_language']);

        return back()->with('success', __('profile.flash.language_updated'));
    }

    public function updateTwoFactor(Request $request): RedirectResponse
    {
        $user = $request->user();
        $enabled = $request->boolean('two_factor_enabled');

        $user->forceFill(['two_factor_enabled' => $enabled])->save();

        $this->audit->log(
            $enabled ? 'profile.two_factor_enabled' : 'profile.two_factor_disabled',
            $user,
            ['name' => $user->translations('name')],
        );

        return back()->with('success', __('profile.flash.two_factor_updated'));
    }
}
