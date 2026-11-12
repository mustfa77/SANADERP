<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AlertType;
use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Services\Alerts\AlertService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * In-system alerts of the user's branches (alerts.view), limited to the
 * types whose subject the user may see; handled with alerts.edit.
 */
class AlertController extends Controller
{
    public function __construct(private AlertService $alerts) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $type = AlertType::tryFrom((string) $request->query('type'));
        $showResolved = $request->boolean('resolved');

        $base = Alert::query()->visibleTo($user);

        return view('admin.alerts.index', [
            'alerts' => (clone $base)
                ->when(! $showResolved, fn ($query) => $query->open(), fn ($query) => $query->whereNotNull('resolved_at'))
                ->when($type, fn ($query) => $query->where('type', $type))
                ->with(['subject', 'resolver', 'branch'])
                ->orderByRaw("case when level = 'danger' then 0 else 1 end")
                ->orderBy('due_at')
                ->paginate(30)
                ->withQueryString(),
            'counts' => (clone $base)->open()->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type'),
            'type' => $type,
            'showResolved' => $showResolved,
            'types' => array_values(array_filter(AlertType::cases(), fn (AlertType $alertType): bool => $user->can($alertType->permission()))),
        ]);
    }

    public function refresh(): RedirectResponse
    {
        $result = $this->alerts->refresh();

        return back()->with('success', __('alerts.flash.refreshed', $result));
    }

    public function resolve(Request $request, Alert $alert): RedirectResponse
    {
        abort_unless($request->user()->can($alert->type->permission()), 403);
        $this->alerts->resolve($alert, $request->user());

        return back()->with('success', __('alerts.flash.resolved'));
    }
}
