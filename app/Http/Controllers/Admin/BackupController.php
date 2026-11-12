<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Manual backups from Settings > Backup & maintenance.
 */
class BackupController extends Controller
{
    public function __construct(private BackupService $backups, private AuditLogger $audit) {}

    public function store(): RedirectResponse
    {
        $redirect = redirect()->route('admin.settings.edit', ['tab' => 'maintenance']);

        try {
            $name = $this->backups->create();
        } catch (Throwable $exception) {
            Log::error('Backup failed', ['error' => $exception->getMessage()]);
            $this->audit->log('backup.failed', params: ['error' => $exception->getMessage()]);

            return $redirect->with('error', __('settings.backup.failed', ['error' => $exception->getMessage()]));
        }

        $this->audit->log('backup.created', params: ['file' => $name]);
        $this->backups->prune(Setting::integer('backup_keep'));

        return $redirect->with('success', __('settings.backup.created', ['file' => $name]));
    }

    public function download(string $backup): BinaryFileResponse
    {
        abort_unless($this->backups->exists($backup), 404);

        $this->audit->log('backup.downloaded', params: ['file' => $backup]);

        return response()->download($this->backups->path($backup));
    }

    public function destroy(string $backup): RedirectResponse
    {
        abort_unless($this->backups->exists($backup), 404);

        $this->backups->delete($backup);
        $this->audit->log('backup.deleted', params: ['file' => $backup]);

        return redirect()->route('admin.settings.edit', ['tab' => 'maintenance'])->with('success', __('settings.backup.deleted'));
    }
}
