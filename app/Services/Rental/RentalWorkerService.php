<?php

namespace App\Services\Rental;

use App\Enums\HourlyBookingStatus;
use App\Enums\RentalType;
use App\Enums\SponsorType;
use App\Enums\WorkerRentalType;
use App\Enums\WorkerStatus;
use App\Models\Worker;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The rental pool: company-sponsored workers and their rental type
 * (monthly / hourly / none), set from the rental screen so staff do not
 * have to open each worker file.
 */
class RentalWorkerService
{
    public function __construct(private AuditLogger $audit, private RentalPricing $pricing) {}

    /**
     * Company-sponsored workers that are available now.
     *
     * @return Builder<Worker>
     */
    public function availableCompanyWorkers(): Builder
    {
        return Worker::query()
            ->where('status', WorkerStatus::Available)
            ->where('sponsor_type', SponsorType::Company);
    }

    /**
     * Move a worker into (or out of) the monthly / hourly rental pool.
     */
    public function assign(Worker $worker, WorkerRentalType $type): Worker
    {
        return DB::transaction(function () use ($worker, $type): Worker {
            $worker = Worker::query()->withoutGlobalScopes()->lockForUpdate()->findOrFail($worker->getKey());
            $old = $worker->rental_type;

            if ($old === $type) {
                return $worker;
            }

            if ($worker->sponsor_type !== SponsorType::Company) {
                throw RentalException::because('not_company_worker');
            }

            // Not while she is out on a monthly contract.
            if ($worker->status === WorkerStatus::RentedMonthly) {
                throw RentalException::because('worker_rented');
            }

            if ($type === WorkerRentalType::Hourly && ! in_array($worker->country_id, $this->pricing->countriesWithPrice(RentalType::Hourly), true)) {
                throw RentalException::because('country_not_hourly');
            }

            // Leaving the hourly pool: no upcoming bookings may remain.
            if ($old === WorkerRentalType::Hourly && $worker->hourlyBookings()->where('status', HourlyBookingStatus::Booked)->where('ends_at', '>', now())->exists()) {
                throw RentalException::because('has_upcoming_bookings');
            }

            $worker->forceFill(['rental_type' => $type])->save();

            $this->audit->log('worker.rental_type_changed', $worker, [
                'name' => $worker->translations('name'),
                'type' => ['ar' => __('workers.rental_types.'.$type->value, [], 'ar'), 'en' => __('workers.rental_types.'.$type->value, [], 'en')],
            ], ['rental_type' => $old?->value], ['rental_type' => $type->value]);

            return $worker;
        });
    }
}
