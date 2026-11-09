@extends('layouts.app')

@section('title', __('dashboard.title'))

@push('styles')
    <!-- jvectormap -->
    <link href="{{ asset('assets/libs/jqvmap/jqvmap.min.css') }}" rel="stylesheet" />
@endpush

@section('content')

    @include('layouts.partials.page-title', ['title' => __('dashboard.title')])

    @php
        $stats = [
            ['chart' => 'radialchart-1', 'label' => 'users', 'value' => '2.2k', 'change' => '0.02%', 'up' => true],
            ['chart' => 'radialchart-2', 'label' => 'views_per_minute', 'value' => '50', 'change' => '1.7%', 'up' => true],
            ['chart' => 'radialchart-3', 'label' => 'bounce_rate', 'value' => '24.03 %', 'change' => '0.01%', 'up' => false],
            ['icon' => 'ri-group-line', 'label' => 'new_visitors', 'value' => '435', 'change' => '0.01%', 'up' => true],
        ];
    @endphp

    <div class="row">
        @foreach ($stats as $stat)
            <div class="col-xl-3 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex text-muted">
                            <div class="flex-shrink-0 me-3 align-self-center">
                                @isset($stat['chart'])
                                    <div id="{{ $stat['chart'] }}" class="apex-charts" dir="ltr"></div>
                                @else
                                    <div class="avatar-sm">
                                        <div class="avatar-title bg-light rounded-circle text-primary font-size-20">
                                            <i class="{{ $stat['icon'] }}"></i>
                                        </div>
                                    </div>
                                @endisset
                            </div>
                            <div class="flex-grow-1 overflow-hidden">
                                <p class="mb-1">{{ __('dashboard.' . $stat['label']) }}</p>
                                <h5 class="mb-3">{{ $stat['value'] }}</h5>
                                <p class="text-truncate mb-0">
                                    <span class="{{ $stat['up'] ? 'text-success' : 'text-danger' }} me-2"> {{ $stat['change'] }}
                                        <i class="{{ $stat['up'] ? 'ri-arrow-right-up-line' : 'ri-arrow-right-down-line' }} align-bottom ms-1"></i></span>
                                    {{ __('dashboard.from_previous') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <!-- end card-body -->
                </div>
                <!-- end card -->
            </div>
            <!-- end col -->
        @endforeach
    </div>
    <!-- end row -->

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="card-title">{{ __('dashboard.overview') }}</h5>
                        </div>
                        <div class="flex-shrink-0">
                            <div>
                                <button type="button" class="btn btn-subtle-secondary btn-sm">{{ __('dashboard.all') }}</button>
                                <button type="button" class="btn btn-subtle-primary btn-sm">{{ __('dashboard.1m') }}</button>
                                <button type="button" class="btn btn-subtle-secondary btn-sm">{{ __('dashboard.6m') }}</button>
                                <button type="button" class="btn btn-subtle-secondary btn-sm">{{ __('dashboard.1y') }}</button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div id="mixed-chart" class="apex-charts" dir="ltr"></div>
                    </div>
                </div>
                <!-- end card-body -->

                <div class="card-body border-top">
                    <div class="text-muted text-center">
                        <div class="row">
                            @foreach ([['primary', 'expenses', '1.2'], ['light', 'maintenance', '2.0'], ['danger', 'profit', '0.4']] as $i => [$color, $label, $pct])
                                <div class="col-4 {{ $i < 2 ? 'border-end' : '' }}">
                                    <div>
                                        <p class="mb-2"><i class="mdi mdi-circle font-size-12 text-{{ $color }} me-1"></i>
                                            {{ __('dashboard.' . $label) }}</p>
                                        <h5 class="font-size-16 mb-0">$ 8,524 <span class="text-success font-size-12"><i
                                                    class="mdi mdi-menu-up font-size-14 me-1"></i>{{ $pct }} %</span>
                                        </h5>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <!-- end card-body -->
            </div>
            <!-- end card -->
        </div>
        <!-- end col -->

        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex  align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="card-title">{{ __('dashboard.social_source') }}</h5>
                        </div>
                        <div class="flex-shrink-0">
                            <select class="form-select form-select-sm mb-0 my-n1">
                                @foreach (['MAY' => 'may', 'AP' => 'april', 'MA' => 'march', 'FE' => 'february', 'JA' => 'january', 'DE' => 'december'] as $value => $month)
                                    <option value="{{ $value }}" @selected($loop->first)>{{ __('dashboard.months.' . $month) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <div id="radialBar-chart" class="apex-charts" dir="ltr"></div>
                    </div>

                    <div class="row">
                        @foreach ([['primary', 'ri-facebook-circle-fill', 'Facebook', 125], ['info', 'ri-twitter-fill', 'Twitter', 112], ['danger', 'ri-instagram-line', 'Instagram', 104]] as [$color, $icon, $name, $sales])
                            <div class="col-4">
                                <div class="social-source text-center mt-3">
                                    <div class="avatar-xs mx-auto mb-3">
                                        <span class="avatar-title rounded-circle bg-{{ $color }} font-size-18">
                                            <i class="ri {{ $icon }} text-white"></i>
                                        </span>
                                    </div>
                                    <h5 class="font-size-15">{{ $name }}</h5>
                                    <p class="text-muted mb-0">{{ __('dashboard.sales', ['count' => $sales]) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <!-- end card-body -->
            </div>
            <!-- end card -->
        </div>
        <!-- end col -->
    </div>
    <!-- end row -->

    <div class="row">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ __('dashboard.order_stats') }}</h5>
                    @php
                        $orders = [
                            ['completed', 'ri-checkbox-circle-line', 'success', 70],
                            ['pending', 'ri-calendar-2-line', 'warning', 45],
                            ['cancel', 'ri-close-circle-line', 'danger', 19],
                        ];
                    @endphp
                    <div>
                        <ul class="list-unstyled">
                            @foreach ($orders as [$label, $icon, $color, $value])
                                <li class="py-3">
                                    <div class="d-flex">
                                        <div class="avatar-xs align-self-center me-3">
                                            <div class="avatar-title rounded-circle bg-light text-primary font-size-18">
                                                <i class="{{ $icon }}"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="text-muted mb-2">{{ __('dashboard.' . $label) }}</p>
                                            <div class="progress progress-sm animated-progess">
                                                <div class="progress-bar bg-{{ $color }}" role="progressbar"
                                                    style="width: {{ $value }}%" aria-valuenow="{{ $value }}" aria-valuemin="0"
                                                    aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <hr>

                    <div class="text-center">
                        <div class="row">
                            @foreach ($orders as [$label, , , $value])
                                <div class="col-4">
                                    <div class="mt-2">
                                        <p class="text-muted mb-2">{{ __('dashboard.' . $label) }}</p>
                                        <h5 class="font-size-16 mb-0">{{ $value }}</h5>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <!-- end card-body -->
            </div>
            <!-- end card -->
        </div>
        <!-- end col -->

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">{{ __('dashboard.notifications') }}</h4>

                    @php
                        $notifications = [
                            ['avatar' => 'avatar-2.jpg', 'title' => 'Scott Elliott', 'text' => __('dashboard.noti_1'), 'time' => __('dashboard.min_ago', ['count' => 20])],
                            ['icon' => 'mdi mdi-account-supervisor', 'title' => __('dashboard.noti_2_title'), 'text' => __('dashboard.noti_2'), 'time' => '9:00 am'],
                            ['avatar' => 'avatar-3.jpg', 'title' => 'Frank Martin', 'text' => __('dashboard.noti_3'), 'time' => '8:54 am'],
                            ['icon' => 'mdi mdi-email-outline', 'title' => __('dashboard.noti_4_title'), 'text' => __('dashboard.noti_4'), 'time' => '27-03-2020'],
                            ['avatar' => 'avatar-4.jpg', 'title' => 'Terry Garrick', 'text' => __('dashboard.noti_5'), 'time' => '27-03-2020'],
                        ];
                    @endphp

                    <div class="pe-3" data-simplebar style="max-height: 287px;">
                        @foreach ($notifications as $n)
                            <a href="#" class="text-body d-block">
                                <div class="d-flex py-3">
                                    <div class="flex-shrink-0 me-3 align-self-center">
                                        @isset($n['avatar'])
                                            <img class="rounded-circle avatar-xs" alt=""
                                                src="{{ asset('assets/images/users/' . $n['avatar']) }}">
                                        @else
                                            <div class="avatar-xs">
                                                <span class="avatar-title bg-primary-subtle  rounded-circle text-primary">
                                                    <i class="{{ $n['icon'] }}"></i>
                                                </span>
                                            </div>
                                        @endisset
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <h5 class="font-size-14 mb-1">{{ $n['title'] }}</h5>
                                        <p class="text-truncate mb-0">{{ $n['text'] }}</p>
                                    </div>
                                    <div class="flex-shrink-0 font-size-13">{{ $n['time'] }}</div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
                <!-- end card-body -->
            </div>
            <!-- end card -->
        </div>
        <!-- end col -->

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">{{ __('dashboard.revenue_by_location') }}</h5>

                    <div>
                        <div id="usa" style="height: 226px" dir="ltr"></div>
                    </div>

                    <div class="text-center mt-4">
                        <a href="#" class="btn btn-primary btn-sm">{{ __('dashboard.view_more') }}</a>
                    </div>
                </div>
                <!-- end card-body -->
            </div>
            <!-- end card -->
        </div>
        <!-- end col -->
    </div>
    <!-- end row -->

    @php
        $transactions = [
            ['avatar' => 'avatar-2.jpg', 'id' => '#AP1234', 'name' => 'David Wiley', 'date' => '02 Nov, 2019', 'price' => '1,234', 'qty' => 1, 'amount' => '1,234', 'status' => ['success', 'confirm']],
            ['letter' => 'W', 'id' => '#AP1235', 'name' => 'Walter Jones', 'date' => '04 Nov, 2019', 'price' => '822', 'qty' => 2, 'amount' => '1,644', 'status' => ['success', 'confirm']],
            ['avatar' => 'avatar-3.jpg', 'id' => '#AP1236', 'name' => 'Eric Ryder', 'date' => '05 Nov, 2019', 'price' => '1,153', 'qty' => 1, 'amount' => '1,153', 'status' => ['danger', 'cancel']],
            ['avatar' => 'avatar-6.jpg', 'id' => '#AP1237', 'name' => 'Kenneth Jackson', 'date' => '06 Nov, 2019', 'price' => '1,365', 'qty' => 1, 'amount' => '1,365', 'status' => ['success', 'confirm']],
            ['letter' => 'R', 'id' => '#AP1238', 'name' => 'Ronnie Spiller', 'date' => '08 Nov, 2019', 'price' => '740', 'qty' => 2, 'amount' => '1,480', 'status' => ['warning', 'pending']],
        ];
    @endphp

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">{{ __('dashboard.latest_transaction') }}</h4>

                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 50px;">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="customCheckall">
                                            <label class="form-check-label" for="customCheckall"></label>
                                        </div>
                                    </th>
                                    <th scope="col" style="width: 60px;"></th>
                                    <th scope="col">{{ __('dashboard.id_name') }}</th>
                                    <th scope="col">{{ __('dashboard.date') }}</th>
                                    <th scope="col">{{ __('dashboard.price') }}</th>
                                    <th scope="col">{{ __('dashboard.quantity') }}</th>
                                    <th scope="col">{{ __('dashboard.amount') }}</th>
                                    <th scope="col">{{ __('dashboard.status') }}</th>
                                    <th scope="col">{{ __('dashboard.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transactions as $t)
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="customCheck{{ $loop->iteration }}">
                                                <label class="form-check-label" for="customCheck{{ $loop->iteration }}"></label>
                                            </div>
                                        </td>
                                        <td>
                                            @isset($t['avatar'])
                                                <img src="{{ asset('assets/images/users/' . $t['avatar']) }}" alt="user"
                                                    class="avatar-xs rounded-circle" />
                                            @else
                                                <div class="avatar-xs">
                                                    <span class="avatar-title rounded-circle bg-primary-subtle  text-success">
                                                        {{ $t['letter'] }}
                                                    </span>
                                                </div>
                                            @endisset
                                        </td>
                                        <td>
                                            <p class="mb-1 font-size-12">{{ $t['id'] }}</p>
                                            <h5 class="font-size-15 mb-0">{{ $t['name'] }}</h5>
                                        </td>
                                        <td>{{ $t['date'] }}</td>
                                        <td>$ {{ $t['price'] }}</td>
                                        <td>{{ $t['qty'] }}</td>
                                        <td>$ {{ $t['amount'] }}</td>
                                        <td>
                                            <i class="mdi mdi-checkbox-blank-circle text-{{ $t['status'][0] }} me-1"></i>
                                            {{ __('dashboard.' . $t['status'][1]) }}
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-outline-success btn-sm">{{ __('dashboard.edit') }}</button>
                                            <button type="button" class="btn btn-outline-danger btn-sm">{{ __('dashboard.cancel') }}</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- end card-body -->
            </div>
            <!-- end card -->
        </div>
        <!-- end col -->
    </div>
    <!-- end row -->

@endsection

@push('scripts')
    <!-- apexcharts js -->
    <script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>

    <!-- jquery.vectormap map -->
    <script src="{{ asset('assets/libs/jqvmap/jquery.vmap.min.js') }}"></script>
    <script src="{{ asset('assets/libs/jqvmap/maps/jquery.vmap.usa.js') }}"></script>

    <script src="{{ asset('assets/js/pages/dashboard.init.js') }}"></script>
@endpush
