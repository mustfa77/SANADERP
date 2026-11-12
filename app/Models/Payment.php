<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Payment received for a rental contract or an hourly booking. The receipt
 * (transfer notice) is on the private disk and served by PaymentController.
 * Recorded and verified only through App\Services\Rental\PaymentService.
 */
#[Fillable(['reference', 'notes'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use BelongsToBranch, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function payable(): MorphTo
    {
        return $this->morphTo()->withoutGlobalScopes();
    }

    /**
     * @return BelongsTo<Lookup, $this>
     */
    public function method(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'payment_method_id')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
