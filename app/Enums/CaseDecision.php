<?php

namespace App\Enums;

enum CaseDecision: string
{
    /** Sponsorship transfer to another employer. */
    case Transfer = 'transfer';

    /** Final exit / deportation to the home country. */
    case Deportation = 'deportation';

    /** Back to work (available again). */
    case ReturnToWork = 'return_to_work';

    public function label(): string
    {
        return __("cases.decisions.{$this->value}");
    }

    /**
     * Worker status right after the decision.
     */
    public function workerStatus(): WorkerStatus
    {
        return match ($this) {
            self::Transfer => WorkerStatus::ForTransfer,
            self::Deportation => WorkerStatus::ForDeportation,
            self::ReturnToWork => WorkerStatus::Available,
        };
    }

    /**
     * Worker status when the case is completed (null = decided and closed at once).
     */
    public function finalStatus(): ?WorkerStatus
    {
        return match ($this) {
            self::Transfer => WorkerStatus::Transferred,
            self::Deportation => WorkerStatus::Deported,
            self::ReturnToWork => null,
        };
    }
}
