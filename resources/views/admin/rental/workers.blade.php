@extends('layouts.app')

@section('title', __('rental.pool.title'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('rental.pool.title')])

    <x-alerts :flash="false" />

    @php
        $tabs = [
            'monthly' => ['icon' => 'ri-calendar-2-line', 'permission' => 'rental_contracts.view'],
            'hourly' => ['icon' => 'ri-time-line', 'permission' => 'hourly_bookings.view'],
            'unassigned' => ['icon' => 'ri-user-add-line', 'permission' => null],
        ];
        $canMonthly = auth()->user()->can('rental_contracts.create');
        $canHourly = auth()->user()->can('hourly_bookings.create');
    @endphp

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                <p class="text-muted mb-0">{{ __('rental.pool.hint') }}</p>
                @can('hourly_bookings.view')
                    <a href="{{ route('admin.hourly.index', ['from' => today()->toDateString(), 'to' => today()->toDateString()]) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="ri-calendar-event-line me-1"></i>{{ __('rental.pool.today_bookings', ['count' => $todayBookings]) }}
                    </a>
                @endcan
            </div>

            <ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
                @foreach ($tabs as $key => $config)
                    @if (! $config['permission'] || auth()->user()->can($config['permission']))
                        <li class="nav-item">
                            <a class="nav-link @if ($tab === $key) active @endif" href="{{ route('admin.rental.workers', [...$filters, 'tab' => $key]) }}">
                                <i class="{{ $config['icon'] }} me-1"></i>{{ __('rental.pool.tabs.'.$key) }}
                                <span class="badge {{ $tab === $key ? 'bg-primary' : 'bg-light text-muted' }} ms-1">{{ $counts[$key] }}</span>
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>

            <form method="GET" action="{{ route('admin.rental.workers') }}" class="row g-2">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="col-lg-4 col-md-6">
                    <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="{{ __('recruitment.search_worker') }}">
                </div>
                @foreach (['country_id' => $countries, 'profession_id' => $professions] as $column => $options)
                    <div class="col-lg-3 col-md-6">
                        <select name="{{ $column }}" class="form-select">
                            <option value="">{{ __('workers.fields.'.$column) }}: {{ __('common.all') }}</option>
                            @foreach ($options as $option)
                                <option value="{{ $option->id }}" @selected((string) ($filters[$column] ?? '') === (string) $option->id)>{{ $option->trans('name') }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
                <div class="col-lg-2 col-md-6 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary flex-fill"><i class="ri-search-line"></i></button>
                    <a href="{{ route('admin.rental.workers', ['tab' => $tab]) }}" class="btn btn-light flex-fill"><i class="ri-close-line"></i></a>
                </div>
            </form>
        </div>
    </div>

    @if ($tab === 'unassigned')
        <div class="alert alert-info py-2"><i class="ri-information-line me-1"></i>{{ __('rental.pool.unassigned_hint') }}</div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-centered table-nowrap table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('rental.fields.worker_id') }}</th>
                            <th>{{ __('workers.fields.country_id') }}</th>
                            <th>{{ __('workers.fields.profession_id') }}</th>
                            <th>{{ __('workers.fields.experience_years') }}</th>
                            @if ($tab === 'hourly')
                                <th>{{ __('rental.pool.next_booking') }}</th>
                            @endif
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($workers as $worker)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @include('admin.workers._avatar', ['worker' => $worker, 'size' => 'xs'])
                                        <div>
                                            <a href="{{ route('admin.workers.show', $worker) }}" class="fw-semibold">{{ $worker->trans('name') }}</a>
                                            <div class="text-muted font-size-12" dir="ltr">{{ $worker->number() }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $worker->country?->trans('nationality') }}</td>
                                <td>{{ $worker->profession?->trans('name') }}</td>
                                <td>{{ $worker->experience_years ?? '—' }}</td>
                                @if ($tab === 'hourly')
                                    <td>
                                        @php
                                            $next = $worker->hourlyBookings->first();
                                        @endphp
                                        @if ($next)
                                            <a href="{{ route('admin.hourly.show', $next) }}" class="font-size-13" dir="ltr">{{ $next->starts_at->format('Y-m-d H:i') }}</a>
                                            @if ($worker->hourlyBookings->count() > 1)
                                                <span class="badge bg-light text-muted">+{{ $worker->hourlyBookings->count() - 1 }}</span>
                                            @endif
                                        @else
                                            <span class="text-success font-size-13">{{ __('rental.pool.free') }}</span>
                                        @endif
                                    </td>
                                @endif
                                <td class="text-end">
                                    <div class="d-inline-flex flex-wrap gap-1 justify-content-end">
                                        @if ($tab === 'monthly')
                                            @if ($canMonthly)
                                                <a href="{{ route('admin.rental.create', ['worker' => $worker->id]) }}" class="btn btn-sm btn-primary"><i class="ri-file-paper-2-line me-1"></i>{{ __('rental.pool.actions.contract') }}</a>
                                                @include('admin.rental._assign-button', ['type' => 'none', 'style' => 'btn-light', 'icon' => 'ri-close-line'])
                                            @endif
                                        @elseif ($tab === 'hourly')
                                            @if ($canHourly)
                                                <a href="{{ route('admin.hourly.create', ['worker' => $worker->id]) }}" class="btn btn-sm btn-primary"><i class="ri-time-line me-1"></i>{{ __('rental.pool.actions.book') }}</a>
                                                <a href="{{ route('admin.hourly.index', ['worker_id' => $worker->id, 'from' => today()->toDateString(), 'to' => today()->addDays(13)->toDateString()]) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('rental.pool.actions.schedule') }}"><i class="ri-calendar-line"></i></a>
                                                @include('admin.rental._assign-button', ['type' => 'none', 'style' => 'btn-light', 'icon' => 'ri-close-line'])
                                            @endif
                                        @else
                                            @if ($canMonthly)
                                                @include('admin.rental._assign-button', ['type' => 'monthly', 'style' => 'btn-outline-primary', 'icon' => 'ri-calendar-2-line'])
                                            @endif
                                            @if ($canHourly)
                                                @if (in_array($worker->country_id, $hourlyCountries, true))
                                                    @include('admin.rental._assign-button', ['type' => 'hourly', 'style' => 'btn-outline-primary', 'icon' => 'ri-time-line'])
                                                @else
                                                    <span class="btn btn-sm btn-outline-secondary disabled" title="{{ __('rental.pool.no_hourly_price') }}"><i class="ri-time-line me-1"></i>{{ __('rental.pool.actions.hourly') }}</span>
                                                @endif
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5">{{ __('rental.pool.empty.'.$tab) }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{ $workers->links() }}
@endsection
