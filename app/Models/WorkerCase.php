<?php

namespace App\Models;

use App\Enums\CaseDecision;
use App\Enums\CaseStatus;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A worker waiting for a decision (number CS-YYYY-0001). Branch scoped.
 * Opened, decided and completed only by App\Services\Cases\WorkerCaseService.
 */
class WorkerCase extends Model
{
    use BelongsToBranch;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['status' => 'open'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CaseStatus::class,
            'decision' => CaseDecision::class,
            'opened_at' => 'datetime',
            'decided_at' => 'datetime',
            'completed_at' => 'date',
            'flight_date' => 'date',
            'transfer_fee' => 'decimal:2',
            'ticket_cost' => 'decimal:2',
        ];
    }

    /**
     * Days since the case was opened (until today or the completion).
     */
    public function ageInDays(): int
    {
        return (int) $this->opened_at->copy()->startOfDay()->diffInDays($this->completed_at ?? today());
    }

    /**
     * @return BelongsTo<Worker, $this>
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class)->withoutGlobalScopes();
    }

    /**
     * The record that caused the case (recruitment order, rental contract...).
     *
     * @return MorphTo<Model, $this>
     */
    public function related(): MorphTo
    {
        return $this->morphTo()->withoutGlobalScopes();
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function newSponsor(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'new_sponsor_client_id')->withoutGlobalScopes();
    }

    /**
     * @return HasMany<WorkerCaseNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(WorkerCaseNote::class)->latest('id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
