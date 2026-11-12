@extends('layouts.app')

@section('title', __('recruitment.create'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('recruitment.create'), 'parent' => __('recruitment.title')])

    <x-alerts :flash="false" />

    @php
        $mode = $canCreateClient ? old('client_mode', 'existing') : 'existing';
        $candidateData = $candidates->map(fn ($worker) => [
            'id' => $worker->id,
            'country' => $worker->country_id,
            'profession' => $worker->profession_id,
            'label' => $worker->number().($worker->cv_reference_no ? ' · '.$worker->cv_reference_no : '').' — '.$worker->trans('name')
                .' — '.$worker->country?->trans('nationality').' · '.$worker->profession?->trans('name').' · '.__('workers.fields.experience_years').': '.$worker->experience_years,
        ])->values();
    @endphp

    <form method="POST" action="{{ route('admin.recruitment.store') }}" novalidate>
        @csrf

        {{-- 1. Client --}}
        <div class="card">
            <div class="card-body">
                <h5 class="font-size-15 mb-3"><span class="badge bg-primary rounded-pill me-1">1</span> {{ __('recruitment.steps.client') }}</h5>

                @if ($canCreateClient)
                    <div class="btn-group mb-3" role="group">
                        <input type="radio" class="btn-check" name="client_mode" id="client_mode_existing" value="existing" @checked($mode === 'existing')>
                        <label class="btn btn-outline-primary" for="client_mode_existing"><i class="ri-user-search-line me-1"></i>{{ __('recruitment.client_modes.existing') }}</label>
                        <input type="radio" class="btn-check" name="client_mode" id="client_mode_new" value="new" @checked($mode === 'new')>
                        <label class="btn btn-outline-primary" for="client_mode_new"><i class="ri-user-add-line me-1"></i>{{ __('recruitment.client_modes.new') }}</label>
                    </div>
                @else
                    <input type="hidden" name="client_mode" value="existing">
                @endif

                <div id="existing-client" @class(['d-none' => $mode === 'new'])>
                    <label class="form-label" for="client_id">{{ __('recruitment.fields.client_id') }} <span class="text-danger">*</span></label>
                    <select name="client_id" id="client_id" class="form-select select2 @error('client_id') is-invalid @enderror">
                        <option value="">{{ __('common.select') }}</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}" @selected((string) old('client_id', $selectedClient) === (string) $client->id)>
                                {{ $client->trans('name') }} — {{ $client->phone }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">{{ __('recruitment.hints.client') }}</div>
                    @error('client_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                @if ($canCreateClient)
                    <div id="new-client" @class(['d-none' => $mode !== 'new'])>
                        <div class="row">
                            @foreach ([
                                'name_ar' => ['dir' => 'rtl', 'required' => true],
                                'name_en' => ['dir' => 'ltr'],
                                'national_id' => ['dir' => 'ltr', 'required' => true, 'maxlength' => 10],
                                'phone' => ['dir' => 'ltr', 'required' => true, 'placeholder' => '05XXXXXXXX', 'type' => 'tel'],
                                'phone2' => ['dir' => 'ltr', 'placeholder' => '05XXXXXXXX', 'type' => 'tel'],
                                'city' => [],
                            ] as $field => $options)
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="client_{{ $field }}">{{ __('clients.fields.'.$field) }} @if ($options['required'] ?? false)<span class="text-danger">*</span>@endif</label>
                                    <input type="{{ $options['type'] ?? 'text' }}" name="client[{{ $field }}]" id="client_{{ $field }}" value="{{ old('client.'.$field) }}"
                                        @isset($options['dir']) dir="{{ $options['dir'] }}" @endisset
                                        @isset($options['maxlength']) maxlength="{{ $options['maxlength'] }}" @endisset
                                        @isset($options['placeholder']) placeholder="{{ $options['placeholder'] }}" @endisset
                                        autocomplete="off" class="form-control @error('client.'.$field) is-invalid @enderror">
                                    @error('client.'.$field) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @endforeach
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="client_branch_id">{{ __('clients.fields.branch_id') }} <span class="text-danger">*</span></label>
                                <select name="client[branch_id]" id="client_branch_id" class="form-select @error('client.branch_id') is-invalid @enderror">
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected((string) old('client.branch_id', auth()->user()->selectedBranchId()) === (string) $branch->id)>{{ $branch->trans('name') }}</option>
                                    @endforeach
                                </select>
                                @error('client.branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="form-text mt-0">{{ __('recruitment.hints.new_client') }}</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- 2. Request --}}
        <div class="card">
            <div class="card-body">
                <h5 class="font-size-15 mb-3"><span class="badge bg-primary rounded-pill me-1">2</span> {{ __('recruitment.steps.request') }}</h5>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="country_id">{{ __('recruitment.fields.country_id') }} <span class="text-danger">*</span></label>
                        <select name="country_id" id="country_id" class="form-select @error('country_id') is-invalid @enderror">
                            <option value="">{{ __('common.select') }}</option>
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}" @selected((string) old('country_id') === (string) $country->id)>{{ $country->trans('name') }}</option>
                            @endforeach
                        </select>
                        @error('country_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="profession_id">{{ __('recruitment.fields.profession_id') }} <span class="text-danger">*</span></label>
                        <select name="profession_id" id="profession_id" class="form-select @error('profession_id') is-invalid @enderror">
                            <option value="">{{ __('common.select') }}</option>
                            @foreach ($professions as $profession)
                                <option value="{{ $profession->id }}" @selected((string) old('profession_id') === (string) $profession->id)>{{ $profession->trans('name') }}</option>
                            @endforeach
                        </select>
                        @error('profession_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="contract_amount">{{ __('recruitment.fields.contract_amount') }}</label>
                        <div class="input-group">
                            <input type="number" name="contract_amount" id="contract_amount" min="0" step="0.01" dir="ltr" value="{{ old('contract_amount') }}" class="form-control @error('contract_amount') is-invalid @enderror">
                            <span class="input-group-text">{{ __('dashboard.sar') }}</span>
                            @error('contract_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="notes">{{ __('recruitment.fields.notes') }}</label>
                        <input type="text" name="notes" id="notes" maxlength="2000" value="{{ old('notes') }}" class="form-control @error('notes') is-invalid @enderror">
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. Worker --}}
        <div class="card">
            <div class="card-body">
                <h5 class="font-size-15 mb-3"><span class="badge bg-primary rounded-pill me-1">3</span> {{ __('recruitment.steps.worker') }}</h5>
                <label class="form-label" for="worker_id">{{ __('recruitment.fields.worker_id') }}</label>
                <select name="worker_id" id="worker_id" class="form-select @error('worker_id') is-invalid @enderror" data-selected="{{ old('worker_id', $selectedWorker) }}"
                    data-placeholder="{{ __('recruitment.search_worker') }}">
                    <option value="">{{ __('recruitment.choose_worker_later') }}</option>
                </select>
                <div class="form-text">{{ __('recruitment.hints.worker') }} <span id="worker-count" class="fw-semibold"></span></div>
                @error('worker_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="btn btn-primary"><i class="ri-save-line align-middle me-1"></i> {{ __('recruitment.save_order') }}</button>
            <a href="{{ route('admin.recruitment.index') }}" class="btn btn-light">{{ __('common.cancel') }}</a>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            // Existing / new client.
            var existing = document.getElementById('existing-client');
            var fresh = document.getElementById('new-client');
            document.querySelectorAll('input[name="client_mode"]').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    existing.classList.toggle('d-none', radio.value === 'new' && radio.checked);
                    if (fresh) {
                        fresh.classList.toggle('d-none', ! (radio.value === 'new' && radio.checked));
                    }
                });
            });

            // Workers offered to the client: filtered by country / profession;
            // choosing a worker fills in its country and profession.
            var candidates = {{ Js::from($candidateData) }};
            var select = document.getElementById('worker_id');
            var country = document.getElementById('country_id');
            var profession = document.getElementById('profession_id');
            var count = document.getElementById('worker-count');
            var placeholder = select.options[0].cloneNode(true);

            var find = function (id) {
                return candidates.find(function (item) { return String(item.id) === String(id); });
            };

            function render() {
                var keep = select.value || select.dataset.selected;
                select.innerHTML = '';
                select.appendChild(placeholder.cloneNode(true));
                var shown = 0;
                candidates.forEach(function (worker) {
                    if ((country.value && String(worker.country) !== country.value) || (profession.value && String(worker.profession) !== profession.value)) {
                        return;
                    }
                    select.appendChild(new Option(worker.label, worker.id, false, String(worker.id) === String(keep)));
                    shown++;
                });
                select.dataset.selected = '';
                count.textContent = '(' + shown + ')';
                $(select).trigger('change.select2');
            }

            // Choosing a worker sets her country and profession.
            function fillFromWorker() {
                var worker = find(select.value);
                if (worker) {
                    country.value = String(worker.country);
                    profession.value = String(worker.profession);
                }
            }

            // Searchable by worker number, CV reference no. or name.
            $(select).select2({ width: '100%', dir: document.documentElement.dir || 'rtl', allowClear: true, placeholder: select.dataset.placeholder });
            $(select).on('change', fillFromWorker);
            country.addEventListener('change', render);
            profession.addEventListener('change', render);

            // Preselected worker (from the "Direct order" button or after a validation error).
            var preselected = find(select.dataset.selected);
            if (preselected && ! country.value && ! profession.value) {
                country.value = String(preselected.country);
                profession.value = String(preselected.profession);
            }
            render();
        })();
    </script>
@endpush
