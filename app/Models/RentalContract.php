<?php

namespace App\Models;

use App\Enums\RentalContractStatus;
use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\HasPayments;
use Closure;
use Database\Factories\RentalContractFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

/**
 * Monthly rental contract (one month). Branch scoped. Created, renewed and
 * closed only by App\Services\Rental\RentalContractService.
 */
#[Fillable(['notes'])]
class RentalContract extends Model
{
    /** @use HasFactory<RentalContractFactory> */
    use BelongsToBranch, HasFactory, HasPayments;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['status' => 'active'];

    private static bool $statusChangeAuthorized = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RentalContractStatus::class,
            'starts_at' => 'date',
            'ends_at' => 'date',
            'closed_at' => 'date',
            'price' => 'decimal:2',
            'discount' => 'decimal:2',
            'vat_percent' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'final_total' => 'decimal:2',
        ];
    }

    /**
     * Workers of the contract over time (replacements), oldest first.
     *
     * @return HasMany<RentalContractWorker, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(RentalContractWorker::class)->orderBy('departed_at')->orderBy('id');
    }

    /**
     * The current worker's period (not returned yet).
     */
    public function currentAssignment(): ?RentalContractWorker
    {
        return $this->assignments()->whereNull('returned_at')->latest('id')->first();
    }

    /**
     * Days covered by the contract (start and end included).
     */
    public function contractDays(): int
    {
        return (int) $this->starts_at->diffInDays($this->ends_at) + 1;
    }

    protected static function booted(): void
    {
        static::updating(function (self $contract): void {
            if ($contract->isDirty('status') && ! self::$statusChangeAuthorized) {
                throw new LogicException('Rental contract status can only be changed through RentalContractService.');
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

    public function payableLabel(): string
    {
        return __('rental.payable', ['number' => $this->number]);
    }

    /**
     * Days left until the end date (negative when past).
     */
    public function daysLeft(): int
    {
        return (int) today()->diffInDays($this->ends_at, false);
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
     * @return BelongsTo<self, $this>
     */
    public function renewedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'renewed_from_id')->withoutGlobalScopes();
    }

    /**
     * @return HasOne<self, $this>
     */
    public function renewal(): HasOne
    {
        return $this->hasOne(self::class, 'renewed_from_id')->withoutGlobalScopes();
    }

    /**
     * @return BelongsTo<Lookup, $this>
     */
    public function closeReason(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'close_reason_id')->withTrashed();
    }

    /**
     * @return BelongsTo<Camp, $this>
     */
    public function returnCamp(): BelongsTo
    {
        return $this->belongsTo(Camp::class, 'return_camp_id')->withoutGlobalScopes()->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
