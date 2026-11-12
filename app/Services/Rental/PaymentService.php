<?php

namespace App\Services\Rental;

use App\Enums\HourlyBookingStatus;
use App\Models\HourlyBooking;
use App\Models\Lookup;
use App\Models\Payment;
use App\Models\RentalContract;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Payments of rental contracts and hourly bookings, with the transfer
 * receipt on the private disk. A payment cannot exceed the balance; it is
 * verified by accounts, and only an unverified payment can be removed.
 */
class PaymentService
{
    /**
     * Payable aliases used in URLs => model and the permission needed to see it.
     *
     * @var array<string, array{model: class-string<Model>, permission: string}>
     */
    public const PAYABLES = [
        'rental' => ['model' => RentalContract::class, 'permission' => 'rental_contracts.view'],
        'hourly' => ['model' => HourlyBooking::class, 'permission' => 'hourly_bookings.view'],
    ];

    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  RentalContract|HourlyBooking  $payable
     * @param  array{amount: float|string, paid_at: string, payment_method_id: int, reference?: ?string, notes?: ?string}  $data
     */
    public function record(Model $payable, array $data, ?UploadedFile $receipt = null): Payment
    {
        return DB::transaction(function () use ($payable, $data, $receipt): Payment {
            $payable = $payable->newQuery()->withoutGlobalScopes()->lockForUpdate()->findOrFail($payable->getKey());

            if ($payable instanceof HourlyBooking && $payable->status === HourlyBookingStatus::Cancelled) {
                throw RentalException::because('payable_cancelled');
            }

            $amount = round((float) $data['amount'], 2);

            if ($amount <= 0 || $amount > $payable->balance()) {
                throw RentalException::because('amount_above_balance', ['balance' => number_format($payable->balance(), 2)]);
            }

            $payment = new Payment(['reference' => $data['reference'] ?? null, 'notes' => $data['notes'] ?? null]);
            $payment->forceFill([
                'branch_id' => $payable->branch_id,
                'amount' => $amount,
                'paid_at' => $data['paid_at'],
                'payment_method_id' => $data['payment_method_id'],
                'received_by' => Auth::id(),
            ]);
            $payment->payable()->associate($payable);

            if ($receipt) {
                $payment->receipt_path = $receipt->store('payments/'.now()->format('Y/m'), 'local');
                $payment->receipt_name = mb_substr($receipt->getClientOriginalName(), 0, 255);
            }

            $payment->save();

            $method = Lookup::query()->withTrashed()->find($data['payment_method_id']);
            $this->audit->log('payment.recorded', $payment, [
                'amount' => number_format($amount, 2),
                'number' => $payable->number,
                'method' => $method?->translations('name'),
            ], new: ['amount' => $amount, 'paid_at' => $data['paid_at'], 'reference' => $data['reference'] ?? null]);

            return $payment;
        });
    }

    public function verify(Payment $payment): Payment
    {
        if ($payment->isVerified()) {
            throw RentalException::because('payment_already_verified');
        }

        $payment->forceFill(['verified_at' => now(), 'verified_by' => Auth::id()])->save();

        $this->audit->log('payment.verified', $payment, ['amount' => number_format((float) $payment->amount, 2), 'number' => $payment->payable?->number]);

        return $payment;
    }

    /**
     * Remove a payment entered by mistake (soft delete; verified payments stay).
     */
    public function delete(Payment $payment): void
    {
        if ($payment->isVerified()) {
            throw RentalException::because('payment_verified_locked');
        }

        $payment->delete();

        $this->audit->log('payment.deleted', $payment, ['amount' => number_format((float) $payment->amount, 2), 'number' => $payment->payable?->number], old: $payment->only(['amount', 'paid_at', 'reference']));
    }

    public function receiptExists(Payment $payment): bool
    {
        return $payment->receipt_path !== null && Storage::disk('local')->exists($payment->receipt_path);
    }
}
