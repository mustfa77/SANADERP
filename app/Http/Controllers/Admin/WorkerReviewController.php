<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WorkerStatus;
use App\Http\Controllers\Controller;
use App\Models\Worker;
use App\Services\Workers\InvalidStatusTransition;
use App\Services\Workers\WorkerStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Candidate screening (candidates.approve): review, approve for selection
 * or reject, only through the workflow.review transitions.
 */
class WorkerReviewController extends Controller
{
    public function __invoke(Request $request, Worker $worker, WorkerStatusService $statuses): RedirectResponse
    {
        $allowed = array_map(fn (WorkerStatus $status): string => $status->value, $statuses->reviewTransitions($worker));

        $validated = $request->validate([
            'status' => ['required', Rule::in($allowed)],
            'reason' => [$request->input('status') === WorkerStatus::Rejected->value ? 'required' : 'nullable', 'string', 'max:1000'],
        ], [], ['status' => __('workers.fields.status'), 'reason' => __('workers.fields.status_reason')]);

        try {
            $statuses->transition($worker, WorkerStatus::from($validated['status']), $validated['reason'] ?? null);
        } catch (InvalidStatusTransition $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.workers.show', [$worker, 'tab' => 'status_history'])->with('success', __('workers.flash.status_changed'));
    }
}
