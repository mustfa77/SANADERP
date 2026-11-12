<?php

namespace App\Models;

use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Concerns\BelongsToBranch;
use Database\Factories\TripFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A transport trip (number TR-YYYY-0001). Branch scoped. Created by
 * App\Services\Transport\TripService (automatic from hourly bookings and
 * home deliveries, or manual) and moved on by the driver.
 */
#[Fillable(['branch_id', 'driver_id', 'scheduled_at', 'status', 'from_place', 'to_place', 'notes'])]
class Trip extends Model
{
    /** @use HasFactory<TripFactory> */
    use BelongsToBranch, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'status' => TripStatus::class,
            'type' => TripType::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * @return BelongsTo<Worker, $this>
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class)->withoutGlobalScopes();
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withoutGlobalScopes();
    }

    /**
     * The booking / order that created the trip.
     *
     * @return MorphTo<Model, $this>
     */
    public function related(): MorphTo
    {
        return $this->morphTo()->withoutGlobalScopes();
    }
}
