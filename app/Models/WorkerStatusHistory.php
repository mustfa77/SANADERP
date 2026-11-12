<?php

namespace App\Models;

use App\Enums\WorkerStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One status change of a worker. from_status is null for the initial status.
 */
#[Fillable(['worker_id', 'from_status', 'to_status', 'reason', 'related_type', 'related_id', 'changed_by', 'changed_at'])]
class WorkerStatusHistory extends Model
{
    protected $table = 'worker_status_history';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => WorkerStatus::class,
            'to_status' => WorkerStatus::class,
            'changed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Worker, $this>
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    /**
     * Record that caused the change (contract, case...), if any.
     *
     * @return MorphTo<Model, $this>
     */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
