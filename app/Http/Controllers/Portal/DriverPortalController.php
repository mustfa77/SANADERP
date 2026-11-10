<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Scopes\BranchScope;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Driver portal: only the driver's own trips.
 */
class DriverPortalController extends Controller
{
    public function trips(Request $request): View
    {
        $trips = Trip::query()
            ->withoutGlobalScope(BranchScope::class)
            ->with('branch')
            ->where('driver_id', $request->user()->getKey())
            ->orderBy('scheduled_at')
            ->paginate(20);

        return view('portal.driver.trips', ['trips' => $trips]);
    }
}
