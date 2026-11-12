@extends('layouts.app')

@section('title', __('audit.title'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('audit.title')])

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h4 class="card-title mb-0">{{ __('audit.title') }} <span class="text-muted font-size-14">({{ $logs->total() }})</span></h4>
                @can('export', App\Models\AuditLog::class)
                    <a href="{{ route('admin.audit-logs.export', $filters) }}" class="btn btn-success waves-effect waves-light">
                        <i class="ri-file-excel-2-line align-middle me-1"></i> {{ __('common.export_excel') }}
                    </a>
                @endcan
            </div>

            <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="row g-2 mb-4">
                <div class="col-lg-3 col-md-6">
                    <select name="user" class="form-select select2">
                        <option value="">{{ __('audit.fields.user') }}: {{ __('common.all') }}</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((string) ($filters['user'] ?? '') === (string) $user->id)>{{ $user->displayName() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <select name="action" class="form-select">
                        <option value="">{{ __('audit.fields.action') }}: {{ __('common.all') }}</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ __('audit.action_names.'.$action) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <select name="target" class="form-select">
                        <option value="">{{ __('audit.fields.target') }}: {{ __('common.all') }}</option>
                        @foreach ($targets as $target)
                            <option value="{{ $target }}" @selected(($filters['target'] ?? '') === $target)>{{ __('audit.targets.'.$target) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-1 col-md-4">
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control" title="{{ __('audit.filters.from') }}" aria-label="{{ __('audit.filters.from') }}">
                </div>
                <div class="col-lg-1 col-md-4">
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control" title="{{ __('audit.filters.to') }}" aria-label="{{ __('audit.filters.to') }}">
                </div>
                <div class="col-lg-2 col-md-12 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary flex-fill"><i class="ri-filter-2-line"></i> {{ __('common.filter') }}</button>
                    <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-light flex-fill">{{ __('common.reset') }}</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-centered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-nowrap">{{ __('audit.fields.date') }}</th>
                            <th>{{ __('audit.fields.user') }}</th>
                            <th>{{ __('audit.fields.action') }}</th>
                            <th>{{ __('audit.fields.description') }}</th>
                            <th>{{ __('audit.fields.ip') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td class="text-nowrap"><span dir="ltr">{{ $log->created_at->format('Y-m-d H:i:s') }}</span></td>
                                <td class="text-nowrap">{{ $log->user?->displayName() ?? __('audit.system') }}</td>
                                <td><span class="badge bg-light text-body font-size-12">{{ $log->actionLabel() }}</span></td>
                                <td>{{ $log->description() }}</td>
                                <td><span dir="ltr">{{ $log->ip_address }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('admin.audit-logs.show', $log) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('common.details') }}" data-bs-toggle="tooltip"><i class="ri-eye-line"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">{{ __('common.no_results') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $logs->links() }}</div>
        </div>
    </div>
@endsection
