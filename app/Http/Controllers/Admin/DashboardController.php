<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Support\DashboardDemoData;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard. Figures are sample data (DashboardDemoData) for display until
 * the operational modules are built; branches are the user's real ones.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardDemoData $demo): View
    {
        $user = $request->user();
        $canSeeFinancials = $user->can('reports.view_financial');

        $branches = Branch::query()
            ->whereKey($user->allowedBranchIds())
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('dashboard', [
            'canSeeFinancials' => $canSeeFinancials,
            'kpis' => array_values(array_filter($demo->kpis(), fn (array $kpi): bool => $canSeeFinancials || ! $kpi['financial'])),
            'overview' => $demo->monthlyOverview(),
            'overviewTotals' => $demo->overviewTotals(),
            'nationalities' => $demo->workersByNationality(),
            'pipeline' => $demo->recruitmentPipeline(),
            'alerts' => $demo->alerts(),
            'branchStats' => $demo->byBranch($branches),
            'contracts' => $demo->latestContracts(),
        ]);
    }
}
