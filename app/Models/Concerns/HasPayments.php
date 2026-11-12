<?php

namespace App\Models\Concerns;

use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A contract or booking that receives payments (and refunds) against the
 * amount due: "final_total" after an early return / cancellation, otherwise
 * "total".
 */
trait HasPayments
{
    /**
     * @return MorphMany<Payment, $this>
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable')->latest('paid_at')->latest('id');
    }

    /**
     * @return MorphMany<Refund, $this>
     */
    public function refunds(): MorphMany
    {
        return $this->morphMany(Refund::class, 'payable')->latest('id');
    }

    /**
     * Load the sums used by paidAmount() (avoids one query per row in lists).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithPaymentTotals(Builder $query): Builder
    {
        return $query->withSum('payments', 'amount')
            ->withSum(['refunds as approved_refunds_sum' => fn (Builder $refunds) => $refunds->where('status', RefundStatus::Approved)], 'amount');
    }

    public function dueTotal(): float
    {
        return round((float) ($this->final_total ?? $this->total), 2);
    }

    /**
     * Received payments.
     */
    public function receivedAmount(): float
    {
        return round((float) ($this->payments_sum_amount ?? $this->payments()->sum('amount')), 2);
    }

    /**
     * Approved refunds.
     */
    public function refundedAmount(): float
    {
        $sum = array_key_exists('approved_refunds_sum', $this->attributes)
            ? $this->attributes['approved_refunds_sum']
            : $this->refunds()->where('status', RefundStatus::Approved)->sum('amount');

        return round((float) $sum, 2);
    }

    /**
     * Net amount paid (payments minus approved refunds).
     */
    public function paidAmount(): float
    {
        return round($this->receivedAmount() - $this->refundedAmount(), 2);
    }

    public function balance(): float
    {
        return round($this->dueTotal() - $this->paidAmount(), 2);
    }

    public function paymentStatus(): PaymentStatus
    {
        $paid = $this->paidAmount();
        $due = $this->dueTotal();

        return match (true) {
            $paid <= 0 && $due > 0 => PaymentStatus::Unpaid,
            $paid < $due => PaymentStatus::Partial,
            default => PaymentStatus::Paid,
        };
    }

    /**
     * Short label used on the payment ("Contract MR-2026-0001").
     */
    abstract public function payableLabel(): string;
}
