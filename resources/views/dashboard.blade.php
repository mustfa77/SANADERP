@extends('layouts.app')

@section('title', __('dashboard.title'))

@section('content')

    @include('layouts.partials.page-title', ['title' => __('dashboard.title')])

    <div class="alert alert-info d-flex align-items-center py-2" role="alert">
        <i class="ri-information-line font-size-18 me-2"></i>
        <div>{{ __('dashboard.demo_notice') }}</div>
    </div>

    {{-- Headline cards --}}
    <div class="row">
        @foreach ($kpis as $kpi)
            <div class="col-xl-3 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex text-muted">
                            <div class="flex-shrink-0 me-3 align-self-center">
                                <div class="avatar-sm">
                                    <div class="avatar-title bg-{{ $kpi['color'] }}-subtle rounded-circle text-{{ $kpi['color'] }} font-size-20">
                                        <i class="{{ $kpi['icon'] }}"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 overflow-hidden">
                                <p class="mb-1 text-truncate">{{ __('dashboard.kpis.'.$kpi['key']) }}</p>
                                <h5 class="mb-3">
                                    {{ number_format($kpi['value']) }}
                                    @if ($kpi['financial'])
                                        <span class="font-size-13 text-muted fw-normal">{{ __('dashboard.sar') }}</span>
                                    @endif
                                </h5>
                                <p class="text-truncate mb-0">
                                    <span class="{{ $kpi['change'] >= 0 ? 'text-success' : 'text-danger' }} me-2" dir="ltr">
                                        {{ $kpi['change'] >= 0 ? '+' : '' }}{{ $kpi['change'] }}%
                                        <i class="{{ $kpi['change'] >= 0 ? 'ri-arrow-right-up-line' : 'ri-arrow-right-down-line' }} align-bottom"></i>
                                    </span>
                                    {{ __('dashboard.from_previous') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        {{-- Revenue & contracts overview --}}
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="card-title">{{ $canSeeFinancials ? __('dashboard.overview') : __('dashboard.overview_contracts') }}</h5>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="badge bg-light text-body font-size-12">{{ __('dashboard.last_12_months') }}</span>
                        </div>
                    </div>
                    <div id="overview-chart" class="apex-charts" dir="ltr"></div>
                </div>

                @if ($canSeeFinancials)
                    <div class="card-body border-top">
                        <div class="text-muted text-center">
                            <div class="row">
                                @foreach ($overviewTotals as $total)
                                    <div class="col-4 {{ ! $loop->last ? 'border-end' : '' }}">
                                        <p class="mb-2 text-truncate"><i class="mdi mdi-circle font-size-12 text-{{ $total['color'] }} me-1"></i>
                                            {{ __('dashboard.totals.'.$total['key']) }}</p>
                                        <h5 class="font-size-16 mb-0">
                                            {{ number_format($total['value']) }} <span class="font-size-12 fw-normal">{{ __('dashboard.sar') }}</span>
                                            <span class="{{ $total['change'] >= 0 ? 'text-success' : 'text-danger' }} font-size-12 d-inline-block" dir="ltr">
                                                <i class="mdi {{ $total['change'] >= 0 ? 'mdi-menu-up' : 'mdi-menu-down' }} font-size-14"></i>{{ abs($total['change']) }}%
                                            </span>
                                        </h5>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Workers by nationality --}}
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ __('dashboard.workers_by_nationality') }}</h5>
                    <div id="nationality-chart" class="apex-charts" dir="ltr"></div>
                    <div class="row mt-2">
                        @foreach ($nationalities as $nationality)
                            <div class="col-6">
                                <div class="d-flex justify-content-between py-1 border-bottom">
                                    <span class="text-muted">{{ __('dashboard.nationalities.'.$nationality['key']) }}</span>
                                    <span class="fw-semibold">{{ $nationality['count'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Recruitment pipeline --}}
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ __('dashboard.recruitment_pipeline') }}</h5>
                    @php
                        $pipelineMax = max(array_column($pipeline, 'count'));
                    @endphp
                    <ul class="list-unstyled mb-0">
                        @foreach ($pipeline as $stage)
                            <li class="py-2">
                                <div class="d-flex">
                                    <div class="avatar-xs align-self-center me-3">
                                        <div class="avatar-title rounded-circle bg-{{ $stage['color'] }}-subtle text-{{ $stage['color'] }} font-size-16">
                                            <i class="{{ $stage['icon'] }}"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between mb-2">
                                            <p class="text-muted mb-0">{{ __('dashboard.pipeline.'.$stage['key']) }}</p>
                                            <span class="fw-semibold">{{ $stage['count'] }}</span>
                                        </div>
                                        <div class="progress progress-sm animated-progess">
                                            <div class="progress-bar bg-{{ $stage['color'] }}" role="progressbar"
                                                style="width: {{ round($stage['count'] / $pipelineMax * 100) }}%"
                                                aria-valuenow="{{ $stage['count'] }}" aria-valuemin="0" aria-valuemax="{{ $pipelineMax }}"></div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        {{-- Follow-up alerts --}}
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ __('dashboard.needs_follow_up') }}</h5>
                    <div class="pe-2" data-simplebar style="max-height: 330px;">
                        @foreach ($alerts as $alert)
                            <div class="d-flex align-items-center py-3 {{ ! $loop->last ? 'border-bottom' : '' }}">
                                <div class="avatar-xs flex-shrink-0 me-3">
                                    <span class="avatar-title rounded-circle bg-{{ $alert['color'] }}-subtle text-{{ $alert['color'] }} font-size-16">
                                        <i class="{{ $alert['icon'] }}"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <h5 class="font-size-14 mb-1 text-truncate">{{ __('dashboard.alerts.'.$alert['key'].'.title') }}</h5>
                                    <p class="text-muted text-truncate mb-0 font-size-13">{{ __('dashboard.alerts.'.$alert['key'].'.hint') }}</p>
                                </div>
                                <span class="badge bg-{{ $alert['color'] }} rounded-pill font-size-12 ms-2">{{ $alert['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- By branch --}}
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ __('dashboard.by_branch') }}</h5>
                    @if ($branchStats === [])
                        <p class="text-muted text-center py-4 mb-0">{{ __('dashboard.no_branches') }}</p>
                    @else
                        <div id="branch-chart" class="apex-charts" dir="ltr"></div>
                        <div class="table-responsive mt-2">
                            <table class="table table-sm table-borderless mb-0 text-center">
                                <thead class="text-muted">
                                    <tr>
                                        <th class="text-start">{{ __('dashboard.branch') }}</th>
                                        <th>{{ __('dashboard.workers') }}</th>
                                        <th>{{ __('dashboard.contracts') }}</th>
                                        @if ($canSeeFinancials)
                                            <th>{{ __('dashboard.revenue') }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($branchStats as $row)
                                        <tr>
                                            <td class="text-start fw-semibold">{{ $row['name'] }}</td>
                                            <td>{{ $row['workers'] }}</td>
                                            <td>{{ $row['contracts'] }}</td>
                                            @if ($canSeeFinancials)
                                                <td>{{ number_format($row['revenue'] / 1000) }}k</td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Latest contracts --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">{{ __('dashboard.latest_contracts') }}</h4>
                    @php
                        $statusColors = ['active' => 'success', 'pending' => 'warning', 'ended' => 'secondary', 'cancelled' => 'danger'];
                        $typeColors = ['monthly' => 'primary', 'hourly' => 'info', 'recruitment' => 'warning'];
                    @endphp
                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('dashboard.table.number') }}</th>
                                    <th>{{ __('dashboard.table.client') }}</th>
                                    <th>{{ __('dashboard.table.worker') }}</th>
                                    <th>{{ __('dashboard.table.type') }}</th>
                                    <th>{{ __('dashboard.table.start') }}</th>
                                    @if ($canSeeFinancials)
                                        <th>{{ __('dashboard.table.amount') }}</th>
                                    @endif
                                    <th>{{ __('dashboard.table.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($contracts as $contract)
                                    <tr>
                                        <td><code dir="ltr">{{ $contract['number'] }}</code></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-xs me-2">
                                                    <span class="avatar-title rounded-circle bg-primary-subtle text-primary">{{ mb_substr($contract['client'], 0, 1) }}</span>
                                                </div>
                                                <h5 class="font-size-14 mb-0">{{ $contract['client'] }}</h5>
                                            </div>
                                        </td>
                                        <td>
                                            {{ $contract['worker'] }}
                                            <p class="text-muted font-size-12 mb-0">{{ __('dashboard.nationalities.'.$contract['nationality']) }}</p>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $typeColors[$contract['type']] }}-subtle text-{{ $typeColors[$contract['type']] }} font-size-12">
                                                {{ __('dashboard.contract_types.'.$contract['type']) }}
                                            </span>
                                        </td>
                                        <td dir="ltr" class="text-start">{{ $contract['start'] }}</td>
                                        @if ($canSeeFinancials)
                                            <td>{{ number_format($contract['amount']) }} {{ __('dashboard.sar') }}</td>
                                        @endif
                                        <td>
                                            <i class="mdi mdi-checkbox-blank-circle text-{{ $statusColors[$contract['status']] }} me-1"></i>
                                            {{ __('dashboard.contract_statuses.'.$contract['status']) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    @php
        $nationalityChart = array_map(fn (array $nationality): array => [
            'label' => __('dashboard.nationalities.'.$nationality['key']),
            'count' => $nationality['count'],
        ], $nationalities);
    @endphp
    <script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script>
        (function () {
            var styles = getComputedStyle(document.body);
            var color = function (name, fallback) {
                return (styles.getPropertyValue('--bs-' + name) || fallback).trim();
            };
            var primary = color('primary', '#0bb197');
            var palette = [primary, color('info', '#4aa3ff'), color('warning', '#f1b44c'), color('danger', '#ff3d60'), color('success', '#2ab57d'), color('secondary', '#74788d')];
            var rtl = document.documentElement.dir === 'rtl';
            var fontFamily = 'Cairo, sans-serif';
            var number = function (value) { return Number(value).toLocaleString(); };

            var overview = @json($overview);
            var showMoney = @json($canSeeFinancials);
            var series = [{ name: @json(__('dashboard.chart.contracts')), type: 'line', data: overview.contracts }];

            if (showMoney) {
                series.unshift(
                    { name: @json(__('dashboard.chart.rental')), type: 'column', data: overview.rental },
                    { name: @json(__('dashboard.chart.recruitment')), type: 'column', data: overview.recruitment }
                );
            }

            new ApexCharts(document.querySelector('#overview-chart'), {
                chart: { height: 330, type: 'line', stacked: false, toolbar: { show: false }, fontFamily: fontFamily },
                series: series,
                stroke: { width: showMoney ? [0, 0, 3] : [3], curve: 'smooth' },
                plotOptions: { bar: { columnWidth: '45%', borderRadius: 3 } },
                colors: showMoney ? [primary, palette[1], palette[2]] : [primary],
                xaxis: { categories: overview.labels },
                yaxis: showMoney
                    ? [
                        { seriesName: series[0].name, opposite: rtl, labels: { formatter: function (v) { return Math.round(v / 1000) + 'k'; } } },
                        { seriesName: series[0].name, show: false },
                        { seriesName: series[2].name, opposite: ! rtl, labels: { formatter: function (v) { return Math.round(v); } } }
                    ]
                    : [{ opposite: rtl, labels: { formatter: function (v) { return Math.round(v); } } }],
                tooltip: { shared: true, intersect: false, y: { formatter: function (v) { return number(v); } } },
                legend: { position: 'top', horizontalAlign: 'center' },
                grid: { borderColor: 'rgba(0,0,0,0.06)' },
                dataLabels: { enabled: false }
            }).render();

            var nationalities = {{ Js::from($nationalityChart) }};
            new ApexCharts(document.querySelector('#nationality-chart'), {
                chart: { height: 250, type: 'donut', fontFamily: fontFamily },
                series: nationalities.map(function (n) { return n.count; }),
                labels: nationalities.map(function (n) { return n.label; }),
                colors: palette,
                legend: { show: false },
                dataLabels: { enabled: false },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '70%',
                            labels: { show: true, total: { show: true, label: @json(__('dashboard.total')), formatter: function (w) { return number(w.globals.seriesTotals.reduce(function (a, b) { return a + b; }, 0)); } } }
                        }
                    }
                }
            }).render();

            var branches = @json($branchStats);
            var branchChart = document.querySelector('#branch-chart');
            if (branchChart && branches.length) {
                new ApexCharts(branchChart, {
                    chart: { height: 200, type: 'bar', toolbar: { show: false }, fontFamily: fontFamily },
                    series: [
                        { name: @json(__('dashboard.workers')), data: branches.map(function (b) { return b.workers; }) },
                        { name: @json(__('dashboard.contracts')), data: branches.map(function (b) { return b.contracts; }) }
                    ],
                    xaxis: { categories: branches.map(function (b) { return b.name; }) },
                    yaxis: { opposite: rtl },
                    colors: [primary, palette[2]],
                    plotOptions: { bar: { columnWidth: '50%', borderRadius: 3 } },
                    legend: { position: 'top' },
                    dataLabels: { enabled: false },
                    grid: { borderColor: 'rgba(0,0,0,0.06)' }
                }).render();
            }
        })();
    </script>
@endpush
