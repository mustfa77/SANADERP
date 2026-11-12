@extends('layouts.app')

@section('title', __('recruitment.follow_up.title'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('recruitment.follow_up.title'), 'parent' => __('recruitment.title')])

    @php
        $sections = [
            'overdue' => ['icon' => 'ri-alarm-warning-line', 'color' => 'danger', 'items' => $overdue],
            'awaiting_delivery' => ['icon' => 'ri-home-4-line', 'color' => 'warning', 'items' => $awaitingDelivery],
            'probation_ending' => ['icon' => 'ri-timer-line', 'color' => 'info', 'items' => $probationEnding],
        ];
    @endphp

    <div class="row">
        @foreach ($sections as $key => $section)
            <div class="col-md-4">
                <a href="#section-{{ $key }}" class="card text-reset">
                    <div class="card-body d-flex align-items-center">
                        <div class="avatar-sm me-3">
                            <span class="avatar-title rounded bg-{{ $section['color'] }}-subtle text-{{ $section['color'] }} font-size-20"><i class="{{ $section['icon'] }}"></i></span>
                        </div>
                        <div>
                            <h4 class="mb-0">{{ $section['items']->count() }}</h4>
                            <div class="text-muted">{{ __('recruitment.follow_up.sections.'.$key) }}</div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    {{-- Overdue travel stages --}}
    <div class="card" id="section-overdue">
        <div class="card-body">
            <h5 class="card-title mb-3 text-danger"><i class="ri-alarm-warning-line me-1"></i>{{ __('recruitment.follow_up.sections.overdue') }}</h5>
            <div class="table-responsive">
                <table class="table table-sm table-centered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('recruitment.fields.number') }}</th>
                            <th>{{ __('recruitment.fields.client_id') }}</th>
                            <th>{{ __('recruitment.fields.worker_id') }}</th>
                            <th>{{ __('recruitment.current_stage') }}</th>
                            <th>{{ __('recruitment.fields.due_at') }}</th>
                            <th>{{ __('recruitment.follow_up.late_days') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($overdue as $order)
                            <tr>
                                <td><a href="{{ route('admin.recruitment.show', $order) }}" dir="ltr">{{ $order->number }}</a></td>
                                <td>{{ $order->client?->trans('name') }}</td>
                                <td>{{ $order->worker?->trans('name') }}</td>
                                <td>{{ $order->currentStage()?->trans('name') }}</td>
                                <td dir="ltr" class="text-start">{{ $order->currentStage()?->due_at?->format('Y-m-d') }}</td>
                                <td class="text-danger fw-semibold">{{ $order->currentStage()?->due_at ? (int) $order->currentStage()->due_at->diffInDays(today()) : '' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">{{ __('recruitment.follow_up.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Arrived, waiting for delivery --}}
    <div class="card" id="section-awaiting_delivery">
        <div class="card-body">
            <h5 class="card-title mb-3 text-warning"><i class="ri-home-4-line me-1"></i>{{ __('recruitment.follow_up.sections.awaiting_delivery') }}</h5>
            <div class="table-responsive">
                <table class="table table-sm table-centered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('recruitment.fields.number') }}</th>
                            <th>{{ __('recruitment.fields.client_id') }}</th>
                            <th>{{ __('recruitment.fields.worker_id') }}</th>
                            <th>{{ __('recruitment.fields.arrived_at') }}</th>
                            <th>{{ __('recruitment.fields.arrival_camp_id') }}</th>
                            <th>{{ __('recruitment.fields.camp_days') }}</th>
                            <th>{{ __('recruitment.follow_up.camp_fee_so_far') }}</th>
                            <th>{{ __('clients.fields.phone') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($awaitingDelivery as $order)
                            <tr @class(['table-warning' => $order->quote['chargeable_days'] > 0])>
                                <td><a href="{{ route('admin.recruitment.show', $order) }}" dir="ltr">{{ $order->number }}</a></td>
                                <td>{{ $order->client?->trans('name') }}</td>
                                <td>{{ $order->worker?->trans('name') }}</td>
                                <td dir="ltr" class="text-start">{{ $order->arrived_at?->format('Y-m-d') }}</td>
                                <td>{{ $order->arrivalCamp?->trans('name') ?? __('recruitment.no_camp') }}</td>
                                <td>{{ $order->quote['camp_days'] }}</td>
                                <td>{{ number_format($order->quote['camp_fee'], 2) }} {{ __('dashboard.sar') }}</td>
                                <td dir="ltr" class="text-start">
                                    @if ($order->client?->phone)
                                        <a href="tel:{{ $order->client->phone }}">{{ $order->client->phone }}</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-3">{{ __('recruitment.follow_up.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Probation ending --}}
    <div class="card" id="section-probation_ending">
        <div class="card-body">
            <h5 class="card-title mb-1 text-info"><i class="ri-timer-line me-1"></i>{{ __('recruitment.follow_up.sections.probation_ending') }}</h5>
            <p class="text-muted font-size-13">{{ __('recruitment.follow_up.probation_hint', ['days' => $probationAlertDays]) }}</p>
            <div class="table-responsive">
                <table class="table table-sm table-centered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('recruitment.fields.number') }}</th>
                            <th>{{ __('recruitment.fields.client_id') }}</th>
                            <th>{{ __('recruitment.fields.worker_id') }}</th>
                            <th>{{ __('recruitment.fields.delivered_at') }}</th>
                            <th>{{ __('recruitment.fields.probation_ends_at') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($probationEnding as $order)
                            @php
                                $left = (int) today()->diffInDays($order->probation_ends_at, false);
                            @endphp
                            <tr @class(['table-danger' => $left < 0])>
                                <td><a href="{{ route('admin.recruitment.show', $order) }}" dir="ltr">{{ $order->number }}</a></td>
                                <td>{{ $order->client?->trans('name') }}</td>
                                <td>{{ $order->worker?->trans('name') }}</td>
                                <td dir="ltr" class="text-start">{{ $order->delivered_at?->format('Y-m-d') }}</td>
                                <td dir="ltr" class="text-start">{{ $order->probation_ends_at->format('Y-m-d') }}</td>
                                <td>{{ $left > 0 ? __('recruitment.days_left', ['count' => $left]) : __('recruitment.probation_ended') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">{{ __('recruitment.follow_up.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
