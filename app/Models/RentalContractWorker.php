<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One worker's period in a monthly contract (departure from the office to
 * the client, return). A replacement closes the row and adds a new one.
 * Written only by App\Services\Rental\RentalContractService.
 */
class RentalContractWorker extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'departed_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<RentalContract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(RentalContract::class, 'rental_contract_id')->withoutGlobalScopes();
    }

    /**
     * @return BelongsTo<Worker, $this>
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class)->withoutGlobalScopes();
    }

    /**
     * @return BelongsTo<Lookup, $this>
     */
    public function returnReason(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'return_reason_id')->withTrashed();
    }

    /**
     * @return BelongsTo<Camp, $this>
     */
    public function returnCamp(): BelongsTo
    {
        return $this->belongsTo(Camp::class, 'return_camp_id')->withoutGlobalScopes()->withTrashed();
    }
}
