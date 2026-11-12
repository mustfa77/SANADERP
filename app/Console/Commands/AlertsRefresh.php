<?php

namespace App\Console\Commands;

use App\Services\Alerts\AlertService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('alerts:refresh')]
#[Description('Recalculate the in-system alerts (expiries, contract / probation ends, overdue stages, absences, trips...)')]
class AlertsRefresh extends Command
{
    public function handle(AlertService $alerts): int
    {
        $result = $alerts->refresh();
        $this->info("Alerts: {$result['created']} new, {$result['updated']} open, {$result['resolved']} closed.");

        return self::SUCCESS;
    }
}
