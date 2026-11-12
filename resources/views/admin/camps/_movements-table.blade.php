{{-- Housing movements (paginator or collection). Expects $movements, $showWorker. --}}
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-centered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('camps.fields.moved_at') }}</th>
                        @if ($showWorker)<th>{{ __('camps.fields.worker_id') }}</th>@endif
                        <th>{{ __('camps.fields.type') }}</th>
                        <th>{{ __('camps.fields.from_camp_id') }}</th>
                        <th>{{ __('camps.fields.to_camp_id') }}</th>
                        <th>{{ __('camps.fields.note') }}</th>
                        <th>{{ __('camps.fields.created_by') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $movement)
                        <tr>
                            <td dir="ltr" class="text-start text-nowrap">{{ $movement->moved_at->format('Y-m-d H:i') }}</td>
                            @if ($showWorker)
                                <td><a href="{{ route('admin.workers.show', $movement->worker) }}">{{ $movement->worker?->trans('name') }}</a></td>
                            @endif
                            <td><span class="badge bg-{{ $movement->type->color() }}-subtle text-{{ $movement->type->color() }}">{{ $movement->type->label() }}</span></td>
                            <td>{{ $movement->fromCamp?->trans('name') ?? '—' }}</td>
                            <td>{{ $movement->toCamp?->trans('name') ?? '—' }}</td>
                            <td class="text-wrap font-size-13">{{ $movement->note }}</td>
                            <td class="font-size-12">{{ $movement->creator?->displayName() ?? __('camps.system') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">{{ __('camps.no_movements') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@if (method_exists($movements, 'links'))
    {{ $movements->links() }}
@endif
