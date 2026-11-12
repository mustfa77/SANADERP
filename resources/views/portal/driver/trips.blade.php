@extends('layouts.portal')
@use('App\Enums\TripStatus')

@section('title', __('portal.my_trips'))
@section('portal_title', __('portal.driver_title'))
@section('portal_nav')
    <li class="nav-item">
        <a class="nav-link active" href="{{ route('driver.trips.index') }}"><i class="ri-bus-line me-2"></i> {{ __('portal.my_trips') }}</a>
    </li>
@endsection

@section('content')
    <h4 class="mb-3">{{ __('portal.my_trips') }}</h4>

    @forelse ($open as $trip)
        <div class="card {{ $trip->status === TripStatus::Started ? 'border border-primary' : '' }}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="font-size-18 fw-bold" dir="ltr">{{ $trip->scheduled_at->format('H:i') }}</div>
                        <div class="text-muted font-size-12" dir="ltr">{{ $trip->scheduled_at->format('Y-m-d') }} · {{ $trip->number }}</div>
                    </div>
                    <span class="badge font-size-12 bg-{{ $trip->status->color() }}-subtle text-{{ $trip->status->color() }}">{{ $trip->status->label() }}</span>
                </div>
                <div class="mb-2"><i class="{{ $trip->type->icon() }} me-1"></i>{{ $trip->type->label() }}</div>
                <div class="mb-1"><i class="ri-map-pin-line text-success me-1"></i>{{ $trip->from_place ?? '—' }}</div>
                <div class="mb-2"><i class="ri-map-pin-fill text-danger me-1"></i>{{ $trip->to_place ?? '—' }}</div>
                @if ($trip->worker)
                    <div class="font-size-13"><i class="ri-user-line me-1"></i>{{ $trip->worker->trans('name') }}</div>
                @endif
                @if ($trip->client)
                    <div class="font-size-13"><i class="ri-phone-line me-1"></i>{{ $trip->client->trans('name') }} — <a href="tel:{{ $trip->client->phone }}" dir="ltr">{{ $trip->client->phone }}</a></div>
                @endif
                @if ($trip->notes)
                    <div class="text-muted font-size-12 mt-1">{{ $trip->notes }}</div>
                @endif

                <div class="d-flex gap-2 mt-3">
                    @if ($trip->status === TripStatus::Scheduled)
                        <form method="POST" action="{{ route('driver.trips.start', $trip->id) }}" class="flex-fill">@csrf<button type="submit" class="btn btn-primary w-100"><i class="ri-play-line me-1"></i>{{ __('transport.actions.start') }}</button></form>
                    @endif
                    <form method="POST" action="{{ route('driver.trips.complete', $trip->id) }}" class="flex-fill">@csrf<button type="submit" class="btn btn-success w-100"><i class="ri-check-double-line me-1"></i>{{ __('transport.actions.complete') }}</button></form>
                </div>
            </div>
        </div>
    @empty
        <div class="card"><div class="card-body text-center text-muted py-5">{{ __('transport.driver_no_trips') }}</div></div>
    @endforelse

    @if ($done->isNotEmpty())
        <h6 class="text-muted mt-4">{{ __('transport.done_today') }}</h6>
        <ul class="list-group mb-4">
            @foreach ($done as $trip)
                <li class="list-group-item d-flex justify-content-between">
                    <span><span dir="ltr">{{ $trip->scheduled_at->format('H:i') }}</span> · {{ $trip->type->label() }} · {{ $trip->to_place }}</span>
                    <span class="badge bg-{{ $trip->status->color() }}-subtle text-{{ $trip->status->color() }}">{{ $trip->status->label() }}</span>
                </li>
            @endforeach
        </ul>
    @endif
@endsection
