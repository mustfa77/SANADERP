<?php

namespace App\Models;

use App\Enums\HourlyBookingStatus;
use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\HasPayments;
use Closure;
use Database\Factories\HourlyBookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Hourly booking (one worker, one date, one shift). Branch scoped. Created,
 * completed and cancelled only by App\Services\Rental\HourlyBookingService.
 * An hourly booking does not change the worker's status (she stays
 * "available").
 */
#[Fillable(['address', 'notes'])]
class HourlyBooking extends Model
{
    /** @use HasFactory<HourlyBookingFactory> */
    use BelongsToBranch, HasFactory, HasPayments;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['status' => 'booked'];

    private static bool $statusChangeAuthorized = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => HourlyBookingStatus::class,
            'date' => 'date',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'hours' => 'decimal:2',
            'hourly_price' => 'decimal:2',
            'price' => 'decimal:2',
            'discount' => 'decimal:2',
            'vat_percent' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'final_total' => 'decimal:2',
            'departed_at' => 'datetime',
            'returned_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $booking): void {
            if ($booking->isDirty('status') && ! self::$statusChangeAuthorized) {
                throw new LogicException('Hourly booking status can only be changed through HourlyBookingService.');
            }
        });
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function withStatusChange(Closure $callback): mixed
    {
        self::$statusChangeAuthorized = true;

        try {
            return $callback();
        } finally {
            self::$statusChangeAuthorized = false;
        }
    }

    /**
     * Minutes the worker came back after the end of the shift (0 if on time).
     */
    public function lateMinutes(): int
    {
        return $this->returned_at && $this->returned_at->gt($this->ends_at)
            ? (int) $this->ends_at->diffInMinutes($this->returned_at)
            : 0;
    }

    public function payableLabel(): string
    {
        return __('hourly.payable', ['number' => $this->number]);
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withoutGlobalScopes();
    }

    /**
     * @return BelongsTo<Worker, $this>
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class)->withoutGlobalScopes();
    }

    /**
     * @return BelongsTo<Shift, $this>
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Lookup, $this>
     */
    public function cancelReason(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'cancel_reason_id')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
