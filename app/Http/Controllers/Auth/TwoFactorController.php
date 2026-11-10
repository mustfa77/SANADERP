<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureTwoFactorVerified;
use App\Notifications\TwoFactorCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->session()->get(EnsureTwoFactorVerified::SESSION_KEY)) {
            return redirect()->route($request->user()->user_type->homeRoute());
        }

        return view('auth.two-factor');
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $user = $request->user();

        $valid = $user->two_factor_code
            && $user->two_factor_expires_at?->isFuture()
            && Hash::check($request->string('code'), $user->two_factor_code);

        if (! $valid) {
            throw ValidationException::withMessages(['code' => __('auth.two_factor.invalid')]);
        }

        $user->forceFill(['two_factor_code' => null, 'two_factor_expires_at' => null])->save();
        $request->session()->forget(EnsureTwoFactorVerified::SESSION_KEY);

        return redirect()->intended(route($user->user_type->homeRoute()));
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->notify(new TwoFactorCode(TwoFactorCode::issue($user)));

        return back()->with('success', __('auth.two_factor.sent'));
    }
}
