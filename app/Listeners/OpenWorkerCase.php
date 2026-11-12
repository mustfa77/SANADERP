<?php

namespace App\Listeners;

use App\Enums\WorkerStatus;
use App\Events\WorkerStatusChanged;
use App\Services\Cases\WorkerCaseService;

/**
 * A worker waiting for a decision gets a case automatically (returned by
 * the client, manual status change...).
 */
class OpenWorkerCase
{
    public function __construct(private WorkerCaseService $cases) {}

    public function handle(WorkerStatusChanged $event): void
    {
        if ($event->to === WorkerStatus::AwaitingDecision) {
            $this->cases->open($event->worker, related: $event->related);
        }
    }
}
