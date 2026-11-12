<?php

namespace App\Models;

use App\Enums\DeliveryMethod;
use App\Enums\RecruitmentStatus;
use App\Models\Concerns\BelongsToBranch;
use Closure;
use Database\Factories\RecruitmentOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * Recruitment order of a client. Branch scoped. Status and the dates of
 * each step are only changed by App\Services\Recruitment\RecruitmentService.
 */
#[Fillable(['branch_id', 'client_id', 'country_id', 'profession_id', 'contract_amount', 'notes'])]
class RecruitmentOrder extends Model
{
    /** @use HasFactory<RecruitmentOrderFactory> */
    use BelongsToBranch, HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['status' => 'new'];

    private static bool $statusChangeAuthorized = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RecruitmentStatus::class,
            'contract_amount' => 'decimal:2',
            'visa_issued_at' => 'date',
            'contracted_at' => 'date',
            'travel_started_at' => 'date',
            'arrived_at' => 'date',
            'delivered_at' => 'date',
            'cancelled_at' => 'datetime',
            'delivery_method' => DeliveryMethod::class,
            'camp_days' => 'integer',
            'camp_fee' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'probation_ends_at' => 'date',
            'completed_at' => 'date',
            'returned_at' => 'date',
        ];
    }

    /**
     * Total charged to the client at delivery (housing + delivery fees).
     */
    public function deliveryFeesTotal(): float
    {
        return (float) $this->camp_fee + (float) $this->delivery_fee;
    }

    /**
     * Probation still running (delivered, not completed or returned).
     */
    public function inProbation(): bool
    {
        return $this->status === RecruitmentStatus::Delivered;
    }

    protected static function booted(): void
    {
        static::updating(function (self $order): void {
            if ($order->isDirty('status') && ! self::$statusChangeAuthorized) {
                throw new LogicException('Recruitment order status can only be changed through RecruitmentService.');
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
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Profession, $this>
     */
    public function profession(): BelongsTo
    {
        return $this->belongsTo(Profession::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Lookup, $this>
     */
    public function cancelReason(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'cancel_reason_id')->withTrashed();
    }

    /**
     * @return BelongsTo<Lookup, $this>
     */
    public function returnReason(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'return_reason_id')->withTrashed();
    }

    /**
     * Housing the worker stayed in between arrival and delivery.
     *
     * @return BelongsTo<Camp, $this>
     */
    public function arrivalCamp(): BelongsTo
    {
        return $this->belongsTo(Camp::class, 'arrival_camp_id')->withoutGlobalScopes()->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function deliverer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<RecruitmentOrderStage, $this>
     */
    public function stages(): HasMany
    {
        return $this->hasMany(RecruitmentOrderStage::class)->orderBy('sort_order');
    }

    /**
     * The stage in progress (first not completed), if any.
     */
    public function currentStage(): ?RecruitmentOrderStage
    {
        return $this->stages->first(fn (RecruitmentOrderStage $stage): bool => $stage->completed_at === null);
    }
}
