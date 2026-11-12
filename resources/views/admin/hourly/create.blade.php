@extends('layouts.app')

@section('title', __('hourly.create'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('hourly.create'), 'parent' => __('hourly.title')])

    <x-alerts :flash="false" />

    @php
        $workerData = $workers->map(fn ($worker) => [
            'id' => $worker->id,
            'rate' => $rates[$worker->country_id]?->price,
            'vat' => $rates[$worker->country_id]?->vat_percent ?? 15,
        ])->keyBy('id');
        $shiftData = $shifts->map(fn ($shift) => ['id' => $shift->id, 'hours' => (float) $shift->hours])->keyBy('id');
    @endphp

    @if ($hourlyCountries === [])
        <div class="alert alert-warning">{{ __('hourly.hints.no_prices') }}</div>
    @elseif ($workers->isEmpty())
        <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2">{{ __('hourly.hints.no_workers') }} <a href="{{ route('admin.rental.workers', ['tab' => 'unassigned']) }}" class="btn btn-sm btn-warning">{{ __('rental.pool.title') }}</a></div>
    @endif

    <form method="POST" action="{{ route('admin.hourly.store') }}" novalidate>
        @csrf
        <div class="card">
            <div class="card-body">
                <p class="text-muted">{{ __('hourly.hints.create') }}</p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="client_id">{{ __('hourly.fields.client_id') }} <span class="text-danger">*</span></label>
                        <select name="client_id" id="client_id" class="form-select select2 @error('client_id') is-invalid @enderror">
                            <option value="">{{ __('common.select') }}</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}" @selected((string) old('client_id', $selectedClient) === (string) $client->id)>{{ $client->trans('name') }} — {{ $client->phone }}</option>
                            @endforeach
                        </select>
                        @error('client_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="worker_id">{{ __('hourly.fields.worker_id') }} <span class="text-danger">*</span> <span class="text-muted font-size-12">({{ $workers->count() }})</span></label>
                        <select name="worker_id" id="worker_id" class="form-select select2 @error('worker_id') is-invalid @enderror">
                            <option value="">{{ __('common.select') }}</option>
                            @foreach ($workers as $worker)
                                <option value="{{ $worker->id }}" @selected((string) old('worker_id', $selectedWorker) === (string) $worker->id)>
                                    {{ $worker->number() }} — {{ $worker->trans('name') }} — {{ $worker->country?->trans('nationality') }} · {{ $worker->branch?->trans('name') }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">{{ __('hourly.hints.workers') }}</div>
                        @error('worker_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="shift_id">{{ __('hourly.fields.shift_id') }} <span class="text-danger">*</span></label>
                        <select name="shift_id" id="shift_id" class="form-select @error('shift_id') is-invalid @enderror">
                            <option value="">{{ __('common.select') }}</option>
                            @foreach ($shifts as $shift)
                                <option value="{{ $shift->id }}" @selected((string) old('shift_id') === (string) $shift->id)>
                                    {{ $shift->trans('name') }} — {{ substr($shift->start_time, 0, 5) }} · {{ (float) $shift->hours }} {{ __('hourly.hours') }}
                                </option>
                            @endforeach
                        </select>
                        @error('shift_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="date">{{ __('hourly.fields.date') }} <span class="text-danger">*</span></label>
                        <input type="date" name="date" id="date" min="{{ today()->toDateString() }}" value="{{ old('date', $selectedDate) }}" required class="form-control @error('date') is-invalid @enderror">
                        @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="days">{{ __('hourly.fields.days') }}</label>
                        <input type="number" name="days" id="days" min="1" max="{{ $maxDays }}" dir="ltr" value="{{ old('days', 1) }}" class="form-control @error('days') is-invalid @enderror">
                        <div class="form-text">{{ __('hourly.hints.days') }}</div>
                        @error('days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="hourly_price">{{ __('hourly.fields.hourly_price') }}</label>
                        <div class="input-group">
                            <input type="number" name="hourly_price" id="hourly_price" step="0.01" min="0" dir="ltr" value="{{ old('hourly_price') }}" class="form-control @error('hourly_price') is-invalid @enderror">
                            <span class="input-group-text">{{ __('dashboard.sar') }}</span>
                        </div>
                        <div class="form-text">{{ __('hourly.hints.rate') }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="discount">{{ __('hourly.fields.discount') }}</label>
                        <div class="input-group">
                            <input type="number" name="discount" id="discount" step="0.01" min="0" dir="ltr" value="{{ old('discount', 0) }}" class="form-control @error('discount') is-invalid @enderror">
                            <span class="input-group-text">{{ __('dashboard.sar') }}</span>
                        </div>
                        <div class="form-text">{{ __('hourly.hints.discount') }}</div>
                        @error('discount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('hourly.fields.total') }}</label>
                        <div class="form-control bg-light" dir="ltr"><strong id="total-each">0.00</strong> <span class="text-muted font-size-12" id="total-all"></span></div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="address">{{ __('hourly.fields.address') }}</label>
                        <input type="text" name="address" id="address" maxlength="500" value="{{ old('address') }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="notes">{{ __('hourly.fields.notes') }}</label>
                        <input type="text" name="notes" id="notes" maxlength="2000" value="{{ old('notes') }}" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>{{ __('hourly.save') }}</button>
            <a href="{{ route('admin.hourly.index') }}" class="btn btn-light">{{ __('common.cancel') }}</a>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            var workers = {{ Js::from($workerData) }};
            var shifts = {{ Js::from($shiftData) }};
            var allLabel = {{ Js::from(__('hourly.total_for_days')) }};
            var worker = document.getElementById('worker_id');
            var shift = document.getElementById('shift_id');
            var rate = document.getElementById('hourly_price');
            var discount = document.getElementById('discount');
            var days = document.getElementById('days');

            // Rate × shift hours − discount, + VAT (per booking).
            function totals() {
                var w = workers[worker.value] || null;
                var s = shifts[shift.value] || null;
                rate.placeholder = w && w.rate ? w.rate : '';
                var perHour = parseFloat(rate.value || rate.placeholder || 0);
                var net = Math.max(0, perHour * (s ? s.hours : 0) - (parseFloat(discount.value) || 0));
                var each = net * (1 + (w ? parseFloat(w.vat) : 15) / 100);
                var count = Math.max(1, parseInt(days.value, 10) || 1);
                document.getElementById('total-each').textContent = each.toFixed(2);
                document.getElementById('total-all').textContent = count > 1 ? allLabel.replace(':count', count).replace(':total', (each * count).toFixed(2)) : '';
            }

            $(worker).on('change', totals);
            [shift, rate, discount, days].forEach(function (input) {
                input.addEventListener('input', totals);
                input.addEventListener('change', totals);
            });
            totals();
        })();
    </script>
@endpush
