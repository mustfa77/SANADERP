<?php

namespace App\Services\Rental;

use App\Enums\HourlyBookingStatus;
use App\Enums\RefundStatus;
use App\Enums\RentalContractStatus;
use App\Models\HourlyBooking;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\RentalContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Rental reports for a period (branch scoped through the models' BranchScope,
 * optionally narrowed to one branch).
 */
class RentalReportService
{
    /**
     * @return array{
     *     operations: array<string, int|float>,
     *     money: array<string, float>,
     *     methods: Collection<int, array{name: string, amount: float, count: int}>,
     *     daily: Collection<string, float>,
     *     outstanding: Collection<int, array{type: string, model: RentalContract|HourlyBooking, balance: float}>,
     *     hourlyWorkers: Collection<int, array{worker: mixed, bookings: int, hours: float, total: float}>,
     *     monthlyWorkers: Collection<int, array{worker: mixed, contracts: int, days: int, total: float}>
     * }
     */
    public function build(Carbon $from, Carbon $to, ?int $branchId = null): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();
        $branch = fn (Builder $query) => $query->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId));

        $contracts = RentalContract::query()->tap($branch)->whereBetween('starts_at', [$from->toDateString(), $to->toDateString()])->with('worker')->get();
        $bookings = HourlyBooking::query()->tap($branch)->whereBetween('starts_at', [$from, $to])->with('worker')->get();
        $live = $bookings->where('status', '!=', HourlyBookingStatus::Cancelled);
        $payments = Payment::query()->tap($branch)->whereBetween('paid_at', [$from->toDateString(), $to->toDateString()])->with('method')->get();

        return [
            'operations' => [
                'contracts_started' => $contracts->count(),
                'renewals' => $contracts->whereNotNull('renewed_from_id')->count(),
                'active_now' => RentalContract::query()->tap($branch)->where('status', RentalContractStatus::Active)->count(),
                'terminated' => RentalContract::query()->tap($branch)->where('status', RentalContractStatus::Terminated)->whereBetween('closed_at', [$from->toDateString(), $to->toDateString()])->count(),
                'bookings' => $live->count(),
                'bookings_completed' => $bookings->where('status', HourlyBookingStatus::Completed)->count(),
                'bookings_cancelled' => $bookings->where('status', HourlyBookingStatus::Cancelled)->count(),
                'hours' => (float) $live->sum(fn (HourlyBooking $booking): float => (float) $booking->hours),
            ],
            'money' => [
                // Amount due: after early returns / cancellations ("final_total").
                'billed_monthly' => round($contracts->sum(fn (RentalContract $contract): float => $contract->dueTotal()), 2),
                'billed_hourly' => round($live->sum(fn (HourlyBooking $booking): float => $booking->dueTotal()), 2),
                'billed' => round($contracts->sum(fn (RentalContract $contract): float => $contract->dueTotal()) + $live->sum(fn (HourlyBooking $booking): float => $booking->dueTotal()), 2),
                'collected' => round((float) $payments->sum('amount'), 2),
                'refunded' => round((float) Refund::query()->tap($branch)->where('status', RefundStatus::Approved)->whereBetween('decided_at', [$from, $to])->sum('amount'), 2),
                'verified' => round((float) $payments->whereNotNull('verified_at')->sum('amount'), 2),
                'unverified' => round((float) $payments->whereNull('verified_at')->sum('amount'), 2),
                'vat' => round((float) $contracts->sum('vat_amount') + (float) $live->sum('vat_amount'), 2),
            ],
            'methods' => $payments->groupBy('payment_method_id')
                ->map(fn (Collection $group): array => ['name' => (string) $group->first()->method?->trans('name'), 'amount' => round((float) $group->sum('amount'), 2), 'count' => $group->count()])
                ->sortByDesc('amount')->values(),
            'daily' => $this->daily($payments, $from, $to),
            'outstanding' => $this->outstanding($branch),
            'hourlyWorkers' => $live->groupBy('worker_id')
                ->map(fn (Collection $group): array => [
                    'worker' => $group->first()->worker,
                    'bookings' => $group->count(),
                    'hours' => (float) $group->sum(fn (HourlyBooking $booking): float => (float) $booking->hours),
                    'total' => round((float) $group->sum('total'), 2),
                ])->sortByDesc('hours')->values(),
            'monthlyWorkers' => $this->monthlyWorkers($branch, $from, $to),
        ];
    }

    /**
     * Collected amount per day of the period (days without payments = 0).
     *
     * @param  Collection<int, Payment>  $payments
     * @return Collection<string, float>
     */
    private function daily(Collection $payments, Carbon $from, Carbon $to): Collection
    {
        $byDay = $payments->groupBy(fn (Payment $payment): string => $payment->paid_at->toDateString());
        $days = collect();

        for ($day = $from->copy(); $day->lte($to) && $days->count() < 366; $day->addDay()) {
            $days->put($day->toDateString(), round((float) ($byDay->get($day->toDateString())?->sum('amount') ?? 0), 2));
        }

        return $days;
    }

    /**
     * Contracts and bookings (not cancelled) that still have a balance,
     * largest first.
     *
     * @return Collection<int, array{type: string, model: RentalContract|HourlyBooking, balance: float}>
     */
    private function outstanding(callable $branch): Collection
    {
        $contracts = RentalContract::query()->tap($branch)->withPaymentTotals()->with(['client', 'worker'])->latest('starts_at')->limit(1000)->get()
            ->map(fn (RentalContract $contract): array => ['type' => 'rental', 'model' => $contract, 'balance' => $contract->balance()]);
        $bookings = HourlyBooking::query()->tap($branch)->where('status', '!=', HourlyBookingStatus::Cancelled)->withPaymentTotals()->with(['client', 'worker'])->latest('starts_at')->limit(1000)->get()
            ->map(fn (HourlyBooking $booking): array => ['type' => 'hourly', 'model' => $booking, 'balance' => $booking->balance()]);

        return $contracts->concat($bookings)->filter(fn (array $row): bool => $row['balance'] > 0)->sortByDesc('balance')->values();
    }

    /**
     * Days each monthly worker was rented within the period.
     *
     * @return Collection<int, array{worker: mixed, contracts: int, days: int, total: float}>
     */
    private function monthlyWorkers(callable $branch, Carbon $from, Carbon $to): Collection
    {
        return RentalContract::query()->tap($branch)
            ->whereDate('starts_at', '<=', $to)
            ->whereDate('ends_at', '>=', $from)
            ->with('worker')
            ->get()
            ->groupBy('worker_id')
            ->map(function (Collection $group) use ($from, $to): array {
                $days = $group->sum(function (RentalContract $contract) use ($from, $to): int {
                    $start = $contract->starts_at->max($from->copy()->startOfDay());
                    $end = ($contract->closed_at ?? $contract->ends_at)->min($to->copy()->startOfDay());

                    return $end->lt($start) ? 0 : (int) $start->diffInDays($end) + 1;
                });

                return ['worker' => $group->first()->worker, 'contracts' => $group->count(), 'days' => $days, 'total' => round((float) $group->sum('total'), 2)];
            })
            ->sortByDesc('days')
            ->values();
    }
}
