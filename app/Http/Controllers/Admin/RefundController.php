<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RefundStatus;
use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Services\Rental\RefundService;
use App\Services\Rental\RentalException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Refund requests of contracts / bookings: list (refunds.view) and the
 * finance decision (refunds.approve).
 */
class RefundController extends Controller
{
    public function __construct(private RefundService $refunds) {}

    public function index(Request $request): View
    {
        $status = RefundStatus::tryFrom((string) $request->query('status', 'pending'));

        return view('admin.refunds.index', [
            'refunds' => Refund::query()
                ->when($status, fn ($query) => $query->where('status', $status))
                ->with(['payable', 'requester', 'decider'])
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'status' => $status,
            'pendingCount' => Refund::query()->where('status', RefundStatus::Pending)->count(),
        ]);
    }

    public function approve(Request $request, Refund $refund): RedirectResponse
    {
        return $this->decide($request, $refund, 'approve');
    }

    public function reject(Request $request, Refund $refund): RedirectResponse
    {
        return $this->decide($request, $refund, 'reject');
    }

    private function decide(Request $request, Refund $refund, string $decision): RedirectResponse
    {
        $note = $request->validate(['decision_note' => ['nullable', 'string', 'max:1000']])['decision_note'] ?? null;

        try {
            $decision === 'approve' ? $this->refunds->approve($refund, $note) : $this->refunds->reject($refund, $note);
        } catch (RentalException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('refunds.flash.'.$decision.'d'));
    }
}
