@extends('layouts.app')

@section('title', __('rental.create'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('rental.create'), 'parent' => __('rental.title')])

    <x-alerts :flash="false" />

    @php
        $workerData = $workers->map(fn ($worker) => [
            'id' => $worker->id,
            'price' => $prices[$worker->country_id]?->price,
            'vat' => $prices[$worker->country_id]?->vat_percent ?? 15,
        ])->keyBy('id');
    @endphp

    <form method="POST" action="{{ route('admin.rental.store') }}" novalidate>
        @csrf
        <div class="card">
            <div class="card-body">
                <p class="text-muted">{{ __('rental.hints.create') }}</p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="client_id">{{ __('rental.fields.client_id') }} <span class="text-danger">*</span></label>
                        <select name="client_id" id="client_id" class="form-select select2 @error('client_id') is-invalid @enderror">
                            <option value="">{{ __('common.select') }}</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}" @selected((string) old('client_id', $selectedClient) === (string) $client->id)>{{ $client->trans('name') }} — {{ $client->phone }}</option>
                            @endforeach
                        </select>
                        @error('client_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        @can('create', App\Models\Client::class)
                            <div class="form-text"><a href="{{ route('admin.clients.create') }}" target="_blank">{{ __('rental.add_client') }}</a></div>
                        @endcan
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="worker_id">{{ __('rental.fields.worker_id') }} <span class="text-danger">*</span> <span class="text-muted font-size-12">({{ $workers->count() }})</span></label>
                        <select name="worker_id" id="worker_id" class="form-select select2 @error('worker_id') is-invalid @enderror">
                            <option value="">{{ __('common.select') }}</option>
                            @foreach ($workers as $worker)
                                <option value="{{ $worker->id }}" @selected((string) old('worker_id', $selectedWorker) === (string) $worker->id)>
                                    {{ $worker->number() }} — {{ $worker->trans('name') }} — {{ $worker->country?->trans('nationality') }} · {{ $worker->profession?->trans('name') }} · {{ $worker->branch?->trans('name') }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">{{ __('rental.hints.workers') }}</div>
                        @error('worker_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="starts_at">{{ __('rental.fields.starts_at') }} <span class="text-danger">*</span></label>
                        <input type="date" name="starts_at" id="starts_at" value="{{ old('starts_at', today()->toDateString()) }}" required class="form-control @error('starts_at') is-invalid @enderror">
                        <div class="form-text" id="ends-at-hint"></div>
                        @error('starts_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="departed_at">{{ __('rental.fields.departed_at') }}</label>
                        <input type="datetime-local" name="departed_at" id="departed_at" value="{{ old('departed_at', now()->format('Y-m-d\TH:i')) }}" class="form-control @error('departed_at') is-invalid @enderror">
                        <div class="form-text">{{ __('rental.hints.departed_at') }}</div>
                        @error('departed_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="price">{{ __('rental.fields.price') }}</label>
                        <div class="input-group">
                            <input type="number" name="price" id="price" step="0.01" min="0" dir="ltr" value="{{ old('price') }}" class="form-control @error('price') is-invalid @enderror">
                            <span class="input-group-text">{{ __('dashboard.sar') }}</span>
                        </div>
                        <div class="form-text">{{ __('rental.hints.price') }}</div>
                        @error('price') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="discount">{{ __('rental.fields.discount') }}</label>
                        <div class="input-group">
                            <input type="number" name="discount" id="discount" step="0.01" min="0" dir="ltr" value="{{ old('discount', 0) }}" class="form-control @error('discount') is-invalid @enderror">
                            <span class="input-group-text">{{ __('dashboard.sar') }}</span>
                        </div>
                        @error('discount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <div class="alert alert-light border mb-0 d-flex flex-wrap gap-4" id="totals">
                            <span>{{ __('rental.fields.vat_amount') }}: <strong id="vat-amount" dir="ltr">0.00</strong></span>
                            <span>{{ __('rental.fields.total') }}: <strong id="total-amount" dir="ltr">0.00</strong> {{ __('dashboard.sar') }}</span>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="notes">{{ __('rental.fields.notes') }}</label>
                        <textarea name="notes" id="notes" rows="2" maxlength="2000" class="form-control">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>{{ __('rental.save') }}</button>
            <a href="{{ route('admin.rental.index') }}" class="btn btn-light">{{ __('common.cancel') }}</a>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            var workers = {{ Js::from($workerData) }};
            var worker = document.getElementById('worker_id');
            var price = document.getElementById('price');
            var discount = document.getElementById('discount');
            var start = document.getElementById('starts_at');
            var endHint = document.getElementById('ends-at-hint');
            var endLabel = {{ Js::from(__('rental.ends_on')) }};

            function current() {
                return workers[worker.value] || null;
            }

            // The worker's country price fills the price (still editable).
            function fillPrice() {
                var data = current();
                price.placeholder = data && data.price ? data.price : '';
                totals();
            }

            function totals() {
                var data = current();
                var base = parseFloat(price.value || price.placeholder || 0);
                var net = Math.max(0, base - (parseFloat(discount.value) || 0));
                var vat = net * (data ? parseFloat(data.vat) : 15) / 100;
                document.getElementById('vat-amount').textContent = vat.toFixed(2);
                document.getElementById('total-amount').textContent = (net + vat).toFixed(2);
            }

            // One month: end = start + 1 month - 1 day.
            function endDate() {
                if (! start.value) {
                    endHint.textContent = '';
                    return;
                }
                var date = new Date(start.value + 'T00:00:00');
                var day = date.getDate();
                date.setMonth(date.getMonth() + 1);
                if (date.getDate() !== day) {
                    date.setDate(0);
                }
                date.setDate(date.getDate() - 1);
                var pad = function (n) { return String(n).padStart(2, '0'); };
                endHint.textContent = endLabel.replace(':date', date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate()));
            }

            $(worker).on('change', fillPrice);
            [price, discount].forEach(function (input) { input.addEventListener('input', totals); });
            start.addEventListener('change', endDate);
            fillPrice();
            endDate();
        })();
    </script>
@endpush
