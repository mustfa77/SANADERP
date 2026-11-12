@extends('layouts.app')

@section('title', __('hourly.title'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('hourly.title')])

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h4 class="card-title mb-0">{{ __('hourly.title') }} <span class="text-muted font-size-14">({{ $count }})</span></h4>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.hourly.index', ['from' => today()->toDateString(), 'to' => today()->toDateString()]) }}" class="btn btn-outline-secondary">{{ __('hourly.today') }}</a>
                    @can('create', App\Models\HourlyBooking::class)
                        <a href="{{ route('admin.hourly.create') }}" class="btn btn-primary"><i class="ri-add-line me-1"></i>{{ __('hourly.create') }}</a>
                    @endcan
                </div>
            </div>

            <form method="GET" action="{{ route('admin.hourly.index') }}" class="row g-2 mb-2">
                <div class="col-lg-2 col-md-4">
                    <label class="form-label font-size-12 mb-1" for="from">{{ __('hourly.from') }}</label>
                    <input type="date" name="from" id="from" value="{{ $from->toDateString() }}" class="form-control">
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label font-size-12 mb-1" for="to">{{ __('hourly.to') }}</label>
                    <input type="date" name="to" id="to" value="{{ $to->toDateString() }}" class="form-control">
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label font-size-12 mb-1" for="worker_filter">{{ __('hourly.fields.worker_id') }}</label>
                    <select name="worker_id" id="worker_filter" class="form-select">
                        <option value="">{{ __('common.all') }}</option>
                        @foreach ($workers as $worker)
                            <option value="{{ $worker->id }}" @selected((string) ($filters['worker_id'] ?? '') === (string) $worker->id)>{{ $worker->trans('name') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label font-size-12 mb-1" for="status_filter">{{ __('hourly.fields.status') }}</label>
                    <select name="status" id="status_filter" class="form-select">
                        <option value="">{{ __('common.all') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label font-size-12 mb-1" for="search">{{ __('common.search') }}</label>
                    <input type="search" name="search" id="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="{{ __('hourly.search_placeholder') }}">
                </div>
                <div class="col-lg-1 col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-outline-primary w-100"><i class="ri-filter-2-line"></i></button>
                </div>
            </form>
        </div>
    </div>

    @forelse ($days as $day => $bookings)
        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between">
                <strong>{{ \Illuminate\Support\Carbon::parse($day)->translatedFormat('l') }} <span dir="ltr">{{ $day }}</span></strong>
                <span class="text-muted">{{ trans_choice('hourly.bookings_count', $bookings->count(), ['count' => $bookings->count()]) }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-centered table-nowrap mb-0">
                        <tbody>
                            @foreach ($bookings as $booking)
                                @php
                                    $payment = $booking->paymentStatus();
                                @endphp
                                <tr>
                                    <td style="width: 140px;" dir="ltr" class="text-start fw-semibold">{{ $booking->starts_at->format('H:i') }} – {{ $booking->ends_at->format('H:i') }}</td>
                                    <td>{{ $booking->shift?->trans('name') }}</td>
                                    <td>{{ $booking->worker?->trans('name') }}</td>
                                    <td>{{ $booking->client?->trans('name') }}<div class="text-muted font-size-12" dir="ltr">{{ $booking->client?->phone }}</div></td>
                                    <td>{{ number_format((float) $booking->total, 2) }}</td>
                                    <td><span class="badge bg-{{ $payment->color() }}-subtle text-{{ $payment->color() }}">{{ $payment->label() }}</span></td>
                                    <td><span class="badge font-size-12 bg-{{ $booking->status->color() }}-subtle text-{{ $booking->status->color() }}">{{ $booking->status->label() }}</span></td>
                                    <td class="text-end"><a href="{{ route('admin.hourly.show', $booking) }}" class="btn btn-sm btn-outline-secondary"><code dir="ltr">{{ $booking->number }}</code></a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @empty
        <div class="card"><div class="card-body text-center text-muted py-5">{{ __('hourly.none_in_period') }}</div></div>
    @endforelse
@endsection
