{{-- Recruitment orders table ($orders). --}}
<div class="table-responsive">
    <table class="table table-sm table-centered mb-0">
        <thead class="table-light">
            <tr>
                <th>{{ __('recruitment.fields.number') }}</th>
                <th>{{ $column === 'worker' ? __('recruitment.fields.worker_id') : __('recruitment.fields.client_id') }}</th>
                <th>{{ __('recruitment.fields.country_id') }}</th>
                <th>{{ __('recruitment.fields.created_at') }}</th>
                <th>{{ __('recruitment.fields.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td>
                        @can('view', $order)
                            <a href="{{ route('admin.recruitment.show', $order) }}"><code dir="ltr">{{ $order->number }}</code></a>
                        @else
                            <code dir="ltr">{{ $order->number }}</code>
                        @endcan
                    </td>
                    <td>{{ $column === 'worker' ? ($order->worker?->trans('name') ?? '—') : $order->client?->trans('name') }}</td>
                    <td>{{ $order->country?->trans('name') }}</td>
                    <td dir="ltr" class="text-start">{{ $order->created_at->format('Y-m-d') }}</td>
                    <td><x-recruitment-status :status="$order->status" /></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted">{{ __('recruitment.no_orders') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
