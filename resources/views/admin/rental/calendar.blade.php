@extends('layouts.app')

@section('title', __('rental.calendar.title'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('rental.calendar.title')])

    @php
        $isArabic = app()->getLocale() === 'ar';
        $calendarConfig = [
            'events' => route('admin.rental.calendar.events'),
            'rtl' => $isArabic,
            // Arabic names with Latin digits and the Gregorian calendar.
            'locale' => $isArabic ? 'ar-u-nu-latn' : 'en',
            'buttons' => __('rental.calendar.buttons'),
            'createUrl' => auth()->user()->can('hourly_bookings.create') ? route('admin.hourly.create') : null,
        ];
    @endphp

    <div class="card">
        <div class="card-body">
            <div class="row g-2 align-items-end mb-3">
                @if ($workers->isNotEmpty())
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label font-size-12 mb-1" for="calendar-worker">{{ __('hourly.fields.worker_id') }}</label>
                        <select id="calendar-worker" class="form-select">
                            <option value="">{{ __('common.all') }}</option>
                            @foreach ($workers as $worker)
                                <option value="{{ $worker->id }}">{{ $worker->trans('name') }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-lg-5 col-md-6 d-flex flex-wrap gap-3">
                    @can('hourly_bookings.view')
                        <div class="form-check">
                            <input class="form-check-input calendar-type" type="checkbox" value="bookings" id="type-bookings" checked>
                            <label class="form-check-label" for="type-bookings">{{ __('rental.calendar.types.bookings') }}</label>
                        </div>
                    @endcan
                    @can('rental_contracts.view')
                        <div class="form-check">
                            <input class="form-check-input calendar-type" type="checkbox" value="contracts" id="type-contracts" checked>
                            <label class="form-check-label" for="type-contracts">{{ __('rental.calendar.types.contracts') }}</label>
                        </div>
                    @endcan
                </div>
                <div class="col-lg-4 d-flex flex-wrap gap-2 justify-content-lg-end font-size-12">
                    @foreach ($colors as $key => $color)
                        <span><i class="ri-checkbox-blank-circle-fill" style="color: {{ $color }}"></i> {{ __('rental.calendar.legend.'.$key) }}</span>
                    @endforeach
                </div>
            </div>

            <div id="rental-calendar"></div>
            @if ($calendarConfig['createUrl'])
                <p class="text-muted font-size-12 mt-2 mb-0"><i class="ri-information-line me-1"></i>{{ __('rental.calendar.hint') }}</p>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/libs/fullcalendar/index.global.min.js') }}"></script>
    <script>
        (function () {
            var config = {{ Js::from($calendarConfig) }};
            var worker = document.getElementById('calendar-worker');

            var calendar = new FullCalendar.Calendar(document.getElementById('rental-calendar'), {
                initialView: window.innerWidth < 768 ? 'listWeek' : 'dayGridMonth',
                locale: { code: config.locale, week: { dow: 6, doy: 12 }, buttonText: config.buttons, allDayText: config.buttons.allDay, noEventsText: config.buttons.noEvents },
                direction: config.rtl ? 'rtl' : 'ltr',
                headerToolbar: { start: 'prev,next today', center: 'title', end: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' },
                height: 'auto',
                dayMaxEvents: 4,
                nowIndicator: true,
                slotMinTime: '06:00:00',
                eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
                events: function (info, success, failure) {
                    var params = new URLSearchParams({ start: info.startStr, end: info.endStr });
                    document.querySelectorAll('.calendar-type:checked').forEach(function (box) { params.append('types[]', box.value); });
                    if (worker && worker.value) {
                        params.append('worker_id', worker.value);
                    }
                    fetch(config.events + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
                        .then(function (response) { return response.json(); })
                        .then(success)
                        .catch(failure);
                },
                eventDidMount: function (info) {
                    info.el.title = info.event.extendedProps.number + ' · ' + info.event.extendedProps.status;
                },
                // Clicking an empty day opens a new booking on that date.
                dateClick: function (info) {
                    if (config.createUrl) {
                        window.location = config.createUrl + '?date=' + info.dateStr.substring(0, 10);
                    }
                }
            });

            calendar.render();

            document.querySelectorAll('.calendar-type').forEach(function (box) {
                box.addEventListener('change', function () { calendar.refetchEvents(); });
            });
            if (worker) {
                worker.addEventListener('change', function () { calendar.refetchEvents(); });
            }
        })();
    </script>
@endpush
