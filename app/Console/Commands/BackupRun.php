<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\BackupService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('backup:run {--scheduled : Run only when automatic backup is enabled and due this hour}')]
#[Description('Back up the database and uploaded files, then remove old backups')]
class BackupRun extends Command
{
    public function handle(BackupService $backups, AuditLogger $audit): int
    {
        $scheduled = (bool) $this->option('scheduled');

        if ($scheduled && ! $this->isDue($backups)) {
            return self::SUCCESS;
        }

        try {
            $name = $backups->create($scheduled ? 'auto' : 'manual');
        } catch (Throwable $exception) {
            $audit->log('backup.failed', params: ['error' => $exception->getMessage()]);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $audit->log('backup.created', params: ['file' => $name]);
        $deleted = $backups->prune(Setting::integer('backup_keep'));

        $this->info("Backup created: {$name}");
        if ($deleted > 0) {
            $this->info("Old backups removed: {$deleted}");
        }

        return self::SUCCESS;
    }

    private function isDue(BackupService $backups): bool
    {
        return Setting::boolean('backup_auto_enabled')
            && now()->hour === Setting::integer('backup_hour')
            && ! $backups->hasAutomaticBackupToday();
    }
}
