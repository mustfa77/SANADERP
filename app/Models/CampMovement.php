<?php

namespace App\Models;

use App\Enums\CampMovementType;
use App\Models\Concerns\BelongsToBranch;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One change of a worker's housing. Written only by the Worker model when
 * camp_id changes; the note comes from CampMovement::withNote() (manual
 * moves) or the worker's latest status change reason.
 */
class CampMovement extends Model
{
    use BelongsToBranch;

    private static ?string $note = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CampMovementType::class,
            'moved_at' => 'datetime',
        ];
    }

    /**
     * Run $callback with the note to record on the movements it causes.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function withNote(?string $note, Closure $callback): mixed
    {
        $previous = self::$note;
        self::$note = $note;

        try {
            return $callback();
        } finally {
            self::$note = $previous;
        }
    }

    /**
     * Record the movement of $worker from $from to $to (camp ids).
     */
    public static function record(Worker $worker, ?int $from, ?int $to): self
    {
        $movement = new self;
        $movement->forceFill([
            'branch_id' => $worker->branch_id,
            'worker_id' => $worker->getKey(),
            'from_camp_id' => $from,
            'to_camp_id' => $to,
            'type' => match (true) {
                $from === null => CampMovementType::In,
                $to === null => CampMovementType::Out,
                default => CampMovementType::Transfer,
            },
            'moved_at' => now(),
            'note' => self::$note ?? self::statusReason($worker),
            'created_by' => auth()->id(),
        ])->save();

        return $movement;
    }

    /**
     * Reason of a status change made in the same moment (arrival, rental...).
     */
    private static function statusReason(Worker $worker): ?string
    {
        $history = WorkerStatusHistory::query()->where('worker_id', $worker->getKey())->latest('id')->first();

        return $history && $history->changed_at?->gte(now()->subSeconds(5)) ? mb_substr((string) $history->reason, 0, 500) : null;
    }

    /**
     * @return BelongsTo<Worker, $this>
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class)->withoutGlobalScopes();
    }

    /**
     * @return BelongsTo<Camp, $this>
     */
    public function fromCamp(): BelongsTo
    {
        return $this->belongsTo(Camp::class, 'from_camp_id')->withoutGlobalScopes()->withTrashed();
    }

    /**
     * @return BelongsTo<Camp, $this>
     */
    public function toCamp(): BelongsTo
    {
        return $this->belongsTo(Camp::class, 'to_camp_id')->withoutGlobalScopes()->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
