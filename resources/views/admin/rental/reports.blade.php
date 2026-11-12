@extends('layouts.app')

@section('title', __('rental.reports.title'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('rental.reports.title')])

    @php
        $ops = $report['operations'];
        $money = $report['money'];
        $sar = __('dashboard.sar');
        $fmt = fn ($amount): string => number_format((float) $amount, 2);
        $chart = ['labels' => $report['daily']->keys()->values(), 'values' => $report['daily']->values(), 'name' => __('rental.reports.money.collected')];
    @endphp

    {{-- Period --}}
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.rental.reports') }}" class="row g-2 align-items-end">
                <div class="col-lg-3 col-md-4">
                    <label class="form-label font-size-12 mb-1" for="from">{{ __('hourly.from') }}</label>
                    <input type="date" name="from" id="from" value="{{ $from->toDateString() }}" class="form-control">
                </div>
                <div class="col-lg-3 col-md-4">
                    <label class="form-label font-size-12 mb-1" for="to">{{ __('hourly.to') }}</label>
                    <input type="date" name="to" id="to" value="{{ $to->toDateString() }}" class="form-control">
                </div>
                @if ($branches->count() > 1)
                    <div class="col-lg-3 col-md-4">
                        <label class="form-label font-size-12 mb-1" for="branch_id">{{ __('rental.fields.branch_id') }}</label>
                        <select name="branch_id" id="branch_id" class="form-select">
                            <option value="">{{ __('common.all') }}</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected($branchId === $branch->id)>{{ $branch->trans('name') }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-lg-3 col-md-12 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary"><i class="ri-bar-chart-2-line me-1"></i>{{ __('rental.reports.show') }}</button>
                    @foreach (['this_month' => [today()->startOfMonth(), today()->endOfMonth()], 'last_month' => [today()->subMonthNoOverflow()->startOfMonth(), today()->subMonthNoOverflow()->endOfMonth()]] as $key => [$start, $end])
                        <a href="{{ route('admin.rental.reports', ['from' => $start->toDateString(), 'to' => $end->toDateString(), 'branch_id' => $branchId]) }}" class="btn btn-light">{{ __('rental.reports.periods.'.$key) }}</a>
                    @endforeach
                </div>
            </form>
        </div>
    </div>

    {{-- Operations --}}
    <div class="row">
        @foreach ([
            'contracts_started' => ['ri-file-paper-2-line', 'primary'],
            'active_now' => ['ri-user-follow-line', 'success'],
            'renewals' => ['ri-refresh-line', 'info'],
            'terminated' => ['ri-logout-box-r-line', 'danger'],
            'bookings' => ['ri-time-line', 'primary'],
            'bookings_completed' => ['ri-check-double-line', 'success'],
            'bookings_cancelled' => ['ri-close-circle-line', 'danger'],
            'hours' => ['ri-timer-line', 'warning'],
        ] as $key => [$icon, $color])
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body d-flex align-items-center">
                        <div class="avatar-sm me-3">
                            <span class="avatar-title rounded bg-{{ $color }}-subtle text-{{ $color }} font-size-20"><i class="{{ $icon }}"></i></span>
                        </div>
                        <div>
                            <h4 class="mb-0">{{ $key === 'hours' ? (float) $ops[$key] : $ops[$key] }}</h4>
                            <div class="text-muted font-size-13">{{ __('rental.reports.operations.'.$key) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($showMoney)
        {{-- Money --}}
        <div class="row">
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">{{ __('rental.reports.sections.money') }}</h5>
                        <table class="table table-sm mb-0">
                            <tbody>
                                @foreach (['billed_monthly', 'billed_hourly', 'billed', 'collected', 'verified', 'unverified', 'refunded', 'vat'] as $key)
                                    <tr @class(['fw-bold' => in_array($key, ['billed', 'collected'], true)])>
                                        <td class="text-muted">{{ __('rental.reports.money.'.$key) }}</td>
                                        <td class="text-end">{{ $fmt($money[$key]) }} <span class="font-size-11 text-muted">{{ $sar }}</span></td>
                                    </tr>
                                @endforeach
                                <tr class="table-danger">
                                    <td>{{ __('rental.reports.money.outstanding') }}</td>
                                    <td class="text-end fw-bold">{{ $fmt($report['outstanding']->sum('balance')) }} <span class="font-size-11">{{ $sar }}</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">{{ __('rental.reports.sections.methods') }}</h5>
                        @forelse ($report['methods'] as $method)
                            <div class="d-flex justify-content-between border-bottom py-1">
                                <span>{{ $method['name'] }} <span class="text-muted font-size-12">({{ $method['count'] }})</span></span>
                                <span>{{ $fmt($method['amount']) }}</span>
                            </div>
                        @empty
                            <p class="text-muted mb-0">{{ __('rental.reports.none') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">{{ __('rental.reports.sections.daily') }}</h5>
                        <div id="daily-chart" dir="ltr" style="min-height: 320px;"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Outstanding balances --}}
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3 text-danger">{{ __('rental.reports.sections.outstanding') }} <span class="text-muted font-size-13">({{ $report['outstanding']->count() }})</span></h5>
                <div class="table-responsive">
                    <table class="table table-sm table-centered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('rental.reports.columns.number') }}</th>
                                <th>{{ __('rental.fields.client_id') }}</th>
                                <th>{{ __('rental.fields.worker_id') }}</th>
                                <th>{{ __('rental.fields.total') }}</th>
                                <th>{{ __('payments.summary.balance') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($report['outstanding']->take(50) as $row)
                                <tr>
                                    <td>
                                        <a href="{{ $row['type'] === 'rental' ? route('admin.rental.show', $row['model']) : route('admin.hourly.show', $row['model']) }}" dir="ltr">{{ $row['model']->number }}</a>
                                        <span class="badge bg-light text-muted">{{ __('rental.reports.types.'.$row['type']) }}</span>
                                    </td>
                                    <td>{{ $row['model']->client?->trans('name') }} <span class="text-muted font-size-12" dir="ltr">{{ $row['model']->client?->phone }}</span></td>
                                    <td>{{ $row['model']->worker?->trans('name') }}</td>
                                    <td>{{ $fmt($row['model']->total) }}</td>
                                    <td class="text-danger fw-semibold">{{ $fmt($row['balance']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">{{ __('rental.reports.no_outstanding') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-light border">{{ __('rental.reports.no_financial') }}</div>
    @endif

    {{-- Workers --}}
    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">{{ __('rental.reports.sections.hourly_workers') }}</h5>
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('rental.fields.worker_id') }}</th>
                                <th>{{ __('rental.reports.columns.bookings') }}</th>
                                <th>{{ __('rental.reports.columns.hours') }}</th>
                                @if ($showMoney)<th>{{ __('rental.fields.total') }}</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($report['hourlyWorkers'] as $row)
                                <tr>
                                    <td>{{ $row['worker']?->trans('name') }}</td>
                                    <td>{{ $row['bookings'] }}</td>
                                    <td>{{ $row['hours'] }}</td>
                                    @if ($showMoney)<td>{{ $fmt($row['total']) }}</td>@endif
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">{{ __('rental.reports.none') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">{{ __('rental.reports.sections.monthly_workers') }}</h5>
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('rental.fields.worker_id') }}</th>
                                <th>{{ __('rental.reports.columns.contracts') }}</th>
                                <th>{{ __('rental.reports.columns.days') }}</th>
                                @if ($showMoney)<th>{{ __('rental.fields.total') }}</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($report['monthlyWorkers'] as $row)
                                <tr>
                                    <td>{{ $row['worker']?->trans('name') }}</td>
                                    <td>{{ $row['contracts'] }}</td>
                                    <td>{{ $row['days'] }}</td>
                                    @if ($showMoney)<td>{{ $fmt($row['total']) }}</td>@endif
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">{{ __('rental.reports.none') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@if ($showMoney)
    @push('scripts')
        <script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>
        <script>
            (function () {
                var data = {{ Js::from($chart) }};
                new ApexCharts(document.getElementById('daily-chart'), {
                    chart: { type: 'bar', height: 320, toolbar: { show: false }, fontFamily: 'inherit' },
                    series: [{ name: data.name, data: data.values }],
                    xaxis: { categories: data.labels, labels: { rotate: -45, formatter: function (value) { return value ? value.substring(5) : value; } } },
                    yaxis: { labels: { formatter: function (value) { return Math.round(value).toLocaleString('en'); } } },
                    colors: [getComputedStyle(document.documentElement).getPropertyValue('--bs-primary').trim() || '#0bb197'],
                    dataLabels: { enabled: false },
                    plotOptions: { bar: { borderRadius: 3, columnWidth: '60%' } },
                    tooltip: { y: { formatter: function (value) { return value.toFixed(2); } } }
                }).render();
            })();
        </script>
    @endpush
@endif
