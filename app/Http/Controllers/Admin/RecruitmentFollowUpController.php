<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DeliveryMethod;
use App\Enums\RecruitmentStatus;
use App\Http\Controllers\Controller;
use App\Models\RecruitmentOrder;
use App\Models\Setting;
use App\Services\Recruitment\RecruitmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

/**
 * Recruitment follow-up (branch scoped): overdue travel stages, arrived
 * workers waiting for delivery (with their housing fees so far) and
 * probations ending soon.
 */
class RecruitmentFollowUpController extends Controller
{
    public function __invoke(RecruitmentService $recruitment): View
    {
        $today = today();
        $probationAlertDays = Setting::integer('days_before_probation_end');

        $overdue = RecruitmentOrder::query()
            ->where('status', RecruitmentStatus::InTravel)
            ->whereHas('stages', fn (Builder $stages) => $stages->whereNull('completed_at')->whereDate('due_at', '<', $today))
            ->with(['client', 'worker', 'country', 'stages'])
            ->get()
            ->sortBy(fn (RecruitmentOrder $order) => $order->currentStage()?->due_at);

        $awaitingDelivery = RecruitmentOrder::query()
            ->where('status', RecruitmentStatus::Arrived)
            ->with(['client', 'worker', 'arrivalCamp'])
            ->orderBy('arrived_at')
            ->get()
            ->each(fn (RecruitmentOrder $order) => $order->setAttribute('quote', $recruitment->deliveryQuote($order, $today->toDateString(), DeliveryMethod::Office)));

        $probationEnding = RecruitmentOrder::query()
            ->where('status', RecruitmentStatus::Delivered)
            ->whereDate('probation_ends_at', '<=', $today->copy()->addDays($probationAlertDays))
            ->with(['client', 'worker'])
            ->orderBy('probation_ends_at')
            ->get();

        return view('admin.recruitment.follow-up', [
            'overdue' => $overdue,
            'awaitingDelivery' => $awaitingDelivery,
            'probationEnding' => $probationEnding,
            'probationAlertDays' => $probationAlertDays,
        ]);
    }
}
