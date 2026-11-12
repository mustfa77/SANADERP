<?php

namespace App\Http\Controllers\Portal;

use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Models\Scopes\BranchScope;
use App\Models\Trip;
use App\Services\Transport\TransportException;
use App\Services\Transport\TripService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Driver portal: only the driver's own trips (branch scope does not apply,
 * the driver_id condition does). The driver starts and completes them.
 */
class DriverPortalController extends Controller
{
    public function __construct(private TripService $trips) {}

    public function trips(Request $request): View
    {
        $own = fn (): Builder => Trip::query()->withoutGlobalScope(BranchScope::class)->where('driver_id', $request->user()->getKey())->with(['branch', 'worker', 'client']);

        return view('portal.driver.trips', [
            // Open trips (today and later, plus any late open ones), then today's finished ones.
            'open' => $own()->whereIn('status', [TripStatus::Scheduled, TripStatus::Started])->whereDate('scheduled_at', '<=', today()->addDays(7))->orderBy('scheduled_at')->get(),
            'done' => $own()->whereIn('status', [TripStatus::Completed, TripStatus::Cancelled])->whereDate('scheduled_at', today())->orderBy('scheduled_at')->get(),
        ]);
    }

    public function start(Request $request, int $trip): RedirectResponse
    {
        return $this->attempt(fn () => $this->trips->start($this->own($request, $trip)), 'transport.flash.started');
    }

    public function complete(Request $request, int $trip): RedirectResponse
    {
        return $this->attempt(fn () => $this->trips->complete($this->own($request, $trip)), 'transport.flash.completed');
    }

    private function own(Request $request, int $id): Trip
    {
        return Trip::query()->withoutGlobalScope(BranchScope::class)->where('driver_id', $request->user()->getKey())->findOrFail($id);
    }

    /**
     * @param  callable(): mixed  $step
     */
    private function attempt(callable $step, string $message): RedirectResponse
    {
        try {
            $step();
        } catch (TransportException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('driver.trips.index')->with('success', __($message));
    }
}
