<?php

namespace App\Services\Rental;

use App\Enums\RefundStatus;
use App\Models\HourlyBooking;
use App\Models\Refund;
use App\Models\RentalContract;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Refund requests (early return, cancellation). A refund cannot exceed the
 * net amount paid less the refunds already pending; finance approves
 * (reduces the amount paid) or rejects it.
 */
class RefundService
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Net paid that can still be refunded (pending requests are reserved).
     *
     * @param  RentalContract|HourlyBooking  $payable
     */
    public function refundable(Model $payable): float
    {
        $pending = (float) $payable->refunds()->where('status', RefundStatus::Pending)->sum('amount');

        return max(0, round($payable->paidAmount() - $pending, 2));
    }

    /**
     * @param  RentalContract|HourlyBooking  $payable
     */
    public function request(Model $payable, float $amount, ?string $reason = null): Refund
    {
        $amount = round($amount, 2);

        if ($amount <= 0 || $amount > $this->refundable($payable)) {
            throw RentalException::because('refund_too_high', ['max' => number_format($this->refundable($payable), 2)]);
        }

        $refund = new Refund(['reason' => $reason]);
        $refund->forceFill([
            'branch_id' => $payable->branch_id,
            'amount' => $amount,
            'requested_by' => Auth::id(),
        ]);
        $refund->payable()->associate($payable);
        $refund->save();

        $this->audit->log('refund.requested', $refund, ['amount' => number_format($amount, 2), 'number' => $payable->number], new: ['amount' => $amount, 'reason' => $reason]);

        return $refund;
    }

    public function approve(Refund $refund, ?string $note = null): Refund
    {
        return $this->decide($refund, RefundStatus::Approved, $note);
    }

    public function reject(Refund $refund, ?string $note = null): Refund
    {
        return $this->decide($refund, RefundStatus::Rejected, $note);
    }

    private function decide(Refund $refund, RefundStatus $status, ?string $note): Refund
    {
        return DB::transaction(function () use ($refund, $status, $note): Refund {
            $refund = Refund::query()->withoutGlobalScopes()->lockForUpdate()->findOrFail($refund->getKey());

            if ($refund->status !== RefundStatus::Pending) {
                throw RentalException::because('refund_decided');
            }

            $refund->forceFill(['status' => $status, 'decided_at' => now(), 'decided_by' => Auth::id(), 'decision_note' => $note])->save();

            $this->audit->log('refund.'.$status->value, $refund, ['amount' => number_format((float) $refund->amount, 2), 'number' => $refund->payable?->number], new: ['note' => $note]);

            return $refund;
        });
    }
}
