<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Top-bar branch switcher. An empty value means "all my branches".
 */
class BranchSwitchController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer', Rule::in($user->allowedBranchIds())],
        ]);

        $request->session()->put(User::SELECTED_BRANCH_SESSION_KEY, $validated['branch_id'] ?? null);

        return back();
    }
}
