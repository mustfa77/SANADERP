<?php

namespace App\Events;

use App\Enums\WorkerStatus;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after every worker status change (after the transaction commits).
 * $from is null for the initial status. $related is the record that caused
 * the change (contract, case...), if any.
 */
class WorkerStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Worker $worker,
        public ?WorkerStatus $from,
        public WorkerStatus $to,
        public ?Model $related = null,
    ) {}
}
