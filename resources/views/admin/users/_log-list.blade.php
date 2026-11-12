{{-- Compact list of audit entries ($logs); $showUser adds the actor name. --}}
<div class="table-responsive">
    <table class="table table-sm mb-0">
        <thead class="table-light">
            <tr>
                <th class="text-nowrap">{{ __('audit.fields.date') }}</th>
                @if ($showUser)
                    <th>{{ __('users.activity.by') }}</th>
                @endif
                <th>{{ __('audit.fields.description') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td class="text-nowrap"><span dir="ltr">{{ $log->created_at->format('Y-m-d H:i') }}</span></td>
                    @if ($showUser)
                        <td class="text-nowrap">{{ $log->user?->displayName() ?? __('audit.system') }}</td>
                    @endif
                    <td>{{ $log->description() }}</td>
                    <td class="text-end">
                        @can('view', $log)
                            <a href="{{ route('admin.audit-logs.show', $log) }}" class="btn btn-sm btn-light"><i class="ri-eye-line"></i></a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ $showUser ? 4 : 3 }}" class="text-center">{{ __('common.no_results') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
