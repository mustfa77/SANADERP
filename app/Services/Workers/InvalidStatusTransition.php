<?php

namespace App\Services\Workers;

use App\Enums\WorkerStatus;
use RuntimeException;

/**
 * A status change not allowed by config/workflow.php.
 */
class InvalidStatusTransition extends RuntimeException
{
    public function __construct(public readonly ?WorkerStatus $from, public readonly WorkerStatus $to, string $reasonKey = 'workers.status.not_allowed')
    {
        parent::__construct(__($reasonKey, [
            'from' => $from?->label() ?? '—',
            'to' => $to->label(),
        ]));
    }
}
