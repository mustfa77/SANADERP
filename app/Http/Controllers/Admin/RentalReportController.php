<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\Rental\RentalReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Rental reports (reports.view). Amounts are shown only with
 * reports.view_financial.
 */
class RentalReportController extends Controller
{
    public function __invoke(Request $request, RentalReportService $reports): View
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $from = Carbon::parse($validated['from'] ?? today()->startOfMonth());
        $to = Carbon::parse($validated['to'] ?? today()->endOfMonth());

        if ($from->diffInDays($to) > 366) {
            $to = $from->copy()->addYear();
        }

        $branches = Branch::query()->whereKey($request->user()->allowedBranchIds())->orderBy('code')->get();
        $branchId = $branches->contains('id', (int) ($validated['branch_id'] ?? 0)) ? (int) $validated['branch_id'] : null;

        return view('admin.rental.reports', [
            'report' => $reports->build($from, $to, $branchId),
            'from' => $from,
            'to' => $to,
            'branchId' => $branchId,
            'branches' => $branches,
            'showMoney' => $request->user()->can('reports.view_financial'),
        ]);
    }
}
