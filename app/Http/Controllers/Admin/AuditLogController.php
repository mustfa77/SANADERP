<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['user', 'action', 'target', 'from', 'to']);

        return view('admin.audit-logs.index', [
            'logs' => $this->filtered($filters)->with('user')->latest('created_at')->latest('id')->paginate(25)->withQueryString(),
            'filters' => $filters,
            'users' => User::withTrashed()->orderBy('name_en')->get(['id', 'name_ar', 'name_en']),
            'actions' => array_keys(Arr::dot(__('audit.action_names'))),
            'targets' => array_keys(__('audit.targets')),
        ]);
    }

    public function show(AuditLog $auditLog): View
    {
        $auditLog->load('user');

        return view('admin.audit-logs.show', ['log' => $auditLog]);
    }

    /**
     * Excel export of the filtered log, in the viewer's language.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $filters = $request->only(['user', 'action', 'target', 'from', 'to']);
        $path = tempnam(sys_get_temp_dir(), 'audit').'.xlsx';
        $writer = SimpleExcelWriter::create($path);

        $this->filtered($filters)->with('user')->latest('created_at')->latest('id')
            ->lazy(500)
            ->each(function (AuditLog $log) use ($writer): void {
                $writer->addRow([
                    __('audit.fields.date') => $log->created_at->format('Y-m-d H:i:s'),
                    __('audit.fields.user') => $log->user?->displayName() ?? __('audit.system'),
                    __('audit.fields.action') => $log->actionLabel(),
                    __('audit.fields.description') => $log->description(),
                    __('audit.fields.target') => trim(($log->targetTypeLabel() ?? '').' #'.($log->target_id ?? ''), ' #'),
                    __('audit.fields.old_values') => $log->old_values ? json_encode($log->old_values, JSON_UNESCAPED_UNICODE) : '',
                    __('audit.fields.new_values') => $log->new_values ? json_encode($log->new_values, JSON_UNESCAPED_UNICODE) : '',
                    __('audit.fields.ip') => $log->ip_address,
                ]);
            });

        $writer->close();

        return response()->download($path, 'audit-log-'.now()->format('Y-m-d').'.xlsx')->deleteFileAfterSend();
    }

    /**
     * @param  array<string, string|null>  $filters
     * @return Builder<AuditLog>
     */
    private function filtered(array $filters): Builder
    {
        return AuditLog::query()
            ->when($filters['user'] ?? null, fn ($q, $user) => $q->where('user_id', $user))
            ->when($filters['action'] ?? null, fn ($q, $action) => $q->where('action_key', $action))
            ->when($filters['target'] ?? null, fn ($q, $target) => $q->where('target_type', 'App\\Models\\'.$target))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('created_at', '<=', $to));
    }
}
