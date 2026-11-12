@extends('layouts.app')

@section('title', __('alerts.title'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('alerts.title')])

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <p class="text-muted mb-0 font-size-13">{{ __('alerts.hint') }}</p>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.alerts.index', ['type' => $type?->value, 'resolved' => $showResolved ? null : 1]) }}" class="btn btn-sm btn-light">
                        {{ $showResolved ? __('alerts.show_open') : __('alerts.show_resolved') }}
                    </a>
                    <form method="POST" action="{{ route('admin.alerts.refresh') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary"><i class="ri-refresh-line me-1"></i>{{ __('alerts.refresh') }}</button>
                    </form>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.alerts.index', ['resolved' => $showResolved ? 1 : null]) }}" class="btn btn-sm {{ $type ? 'btn-light' : 'btn-primary' }}">{{ __('common.all') }} <span class="badge bg-white text-dark ms-1">{{ $counts->sum() }}</span></a>
                @foreach ($types as $option)
                    <a href="{{ route('admin.alerts.index', ['type' => $option->value, 'resolved' => $showResolved ? 1 : null]) }}" class="btn btn-sm {{ $type === $option ? 'btn-primary' : 'btn-light' }}">
                        <i class="{{ $option->icon() }} me-1"></i>{{ $option->label() }}
                        @if ($counts[$option->value] ?? 0)<span class="badge bg-danger ms-1">{{ $counts[$option->value] }}</span>@endif
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-centered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('alerts.fields.type') }}</th>
                            <th>{{ __('alerts.fields.message') }}</th>
                            <th>{{ __('alerts.fields.due_at') }}</th>
                            <th>{{ __('alerts.fields.branch') }}</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($alerts as $alert)
                            <tr>
                                <td class="text-nowrap">
                                    <span class="badge bg-{{ $alert->level }}-subtle text-{{ $alert->level }}"><i class="{{ $alert->type->icon() }} me-1"></i>{{ $alert->type->label() }}</span>
                                </td>
                                <td>{{ $alert->message() }}</td>
                                <td dir="ltr" class="text-start text-nowrap {{ $alert->due_at?->lt(today()) ? 'text-danger' : '' }}">{{ $alert->due_at?->format('Y-m-d') ?? '—' }}</td>
                                <td>{{ $alert->branch?->trans('name') }}</td>
                                <td class="text-end text-nowrap">
                                    @if ($alert->url())
                                        <a href="{{ $alert->url() }}" class="btn btn-sm btn-outline-secondary"><i class="ri-eye-line"></i></a>
                                    @endif
                                    @if ($alert->resolved_at)
                                        <span class="text-muted font-size-12">{{ $alert->resolver?->displayName() ?? __('alerts.auto_resolved') }} · <span dir="ltr">{{ $alert->resolved_at->format('Y-m-d') }}</span></span>
                                    @else
                                        @can('alerts.edit')
                                            <form method="POST" action="{{ route('admin.alerts.resolve', $alert) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success"><i class="ri-check-line me-1"></i>{{ __('alerts.resolve') }}</button>
                                            </form>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-5">{{ __('alerts.none') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{ $alerts->links() }}
@endsection
