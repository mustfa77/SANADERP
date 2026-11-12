<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Automatic backup: checked every hour, runs once a day at the hour chosen
| in Settings > Backup & maintenance (when enabled).
*/
Schedule::command('backup:run --scheduled')->hourly()->withoutOverlapping();

/*
| Remove audit log entries and login attempts older than the retention
| period in settings (0 = keep forever).
*/
Schedule::command('model:prune')->dailyAt('03:30');

/*
| In-system alerts (expiries, contract / probation ends, overdue stages,
| absences, trips without a driver...), recalculated every hour.
*/
Schedule::command('alerts:refresh')->hourly()->withoutOverlapping();
