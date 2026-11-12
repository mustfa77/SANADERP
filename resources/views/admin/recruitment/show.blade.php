@extends('layouts.app')
@use('App\Enums\RecruitmentStatus')

@section('title', $order->number)

@section('content')
    @include('layouts.partials.page-title', ['title' => __('recruitment.show', ['number' => $order->number]), 'parent' => __('recruitment.title')])

    <x-alerts :flash="false" />

    @php
        $canEdit = auth()->user()->can('update', $order);
        $steps = [
            RecruitmentStatus::New->value => $order->created_at,
            RecruitmentStatus::CandidateSelected->value => null,
            RecruitmentStatus::Contracted->value => $order->contracted_at,
            RecruitmentStatus::InTravel->value => $order->travel_started_at,
            RecruitmentStatus::Arrived->value => $order->arrived_at,
            RecruitmentStatus::Delivered->value => $order->delivered_at,
            RecruitmentStatus::Completed->value => $order->completed_at,
        ];
        $currentStage = $order->currentStage();
        $today = now()->toDateString();
        // A returned order went through delivery but not completion.
        $progressStatus = $order->status === RecruitmentStatus::Returned ? RecruitmentStatus::Delivered : $order->status;
        $wasDelivered = $order->delivered_at !== null;
    @endphp

    <div class="row">
        {{-- Order details --}}
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="mb-0"><code dir="ltr">{{ $order->number }}</code></h5>
                        <x-recruitment-status :status="$order->status" />
                    </div>
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <th class="text-muted fw-normal" style="width: 45%;">{{ __('recruitment.fields.client_id') }}</th>
                                <td>
                                    @can('view', $order->client)
                                        <a href="{{ route('admin.clients.show', $order->client) }}">{{ $order->client->trans('name') }}</a>
                                    @else
                                        {{ $order->client?->trans('name') }}
                                    @endcan
                                </td>
                            </tr>
                            <tr><th class="text-muted fw-normal">{{ __('recruitment.fields.country_id') }}</th><td>{{ $order->country?->trans('name') }}</td></tr>
                            <tr><th class="text-muted fw-normal">{{ __('recruitment.fields.profession_id') }}</th><td>{{ $order->profession?->trans('name') }}</td></tr>
                            <tr><th class="text-muted fw-normal">{{ __('recruitment.fields.branch_id') }}</th><td>{{ $order->branch?->trans('name') }}</td></tr>
                            <tr>
                                <th class="text-muted fw-normal">{{ __('recruitment.fields.worker_id') }}</th>
                                <td>
                                    @if ($order->worker)
                                        <a href="{{ route('admin.workers.show', $order->worker) }}">{{ $order->worker->trans('name') }}</a>
                                        <div><x-worker-status :status="$order->worker->status" /></div>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            <tr><th class="text-muted fw-normal">{{ __('recruitment.fields.visa_number') }}</th><td dir="ltr" class="text-start">{{ $order->visa_number ?? '—' }}{{ $order->visa_issued_at ? ' · '.$order->visa_issued_at->format('Y-m-d') : '' }}</td></tr>
                            <tr><th class="text-muted fw-normal">{{ __('recruitment.fields.musaned_contract_no') }}</th><td dir="ltr" class="text-start">{{ $order->musaned_contract_no ?? '—' }}</td></tr>
                            <tr><th class="text-muted fw-normal">{{ __('recruitment.fields.contract_amount') }}</th><td>{{ $order->contract_amount !== null ? number_format((float) $order->contract_amount, 2).' '.__('dashboard.sar') : '—' }}</td></tr>
                            <tr><th class="text-muted fw-normal">{{ __('recruitment.fields.created_by') }}</th><td>{{ $order->creator?->displayName() ?? '—' }}</td></tr>
                            @if ($order->status === RecruitmentStatus::Cancelled)
                                <tr><th class="text-muted fw-normal">{{ __('recruitment.fields.cancel_reason_id') }}</th><td class="text-danger">{{ $order->cancelReason?->trans('name') }}{{ $order->cancel_note ? ' — '.$order->cancel_note : '' }}</td></tr>
                            @endif
                            @if ($order->arrived_at)
                                <tr><th class="text-muted fw-normal">{{ __('recruitment.fields.arrival_camp_id') }}</th><td>{{ $order->arrivalCamp?->trans('name') ?? __('recruitment.no_camp') }}</td></tr>
                            @endif
                        </tbody>
                    </table>
                    @if ($order->notes)
                        <p class="text-muted border-top pt-2 mt-2 mb-0" style="white-space: pre-line;">{{ $order->notes }}</p>
                    @endif
                </div>
            </div>

            {{-- Delivery to the client --}}
            @if ($wasDelivered)
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="card-title mb-0"><i class="ri-hand-heart-line me-1"></i>{{ __('recruitment.delivery_details') }}</h5>
                            <a href="{{ route('admin.recruitment.handover', $order) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="ri-printer-line me-1"></i>{{ __('recruitment.actions.print_handover') }}
                            </a>
                        </div>
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                                <tr><th class="text-muted fw-normal" style="width: 45%;">{{ __('recruitment.fields.delivered_at') }}</th><td dir="ltr" class="text-start">{{ $order->delivered_at->format('Y-m-d') }}</td></tr>
                                <tr><th class="text-muted fw-normal">{{ __('recruitment.fields.delivery_method') }}</th><td>{{ $order->delivery_method?->label() }}</td></tr>
                                <tr><th class="text-muted fw-normal">{{ __('recruitment.fields.receiver_name') }}</th><td>{{ $order->receiver_name }}@if ($order->receiver_phone) <div dir="ltr" class="text-start text-muted">{{ $order->receiver_phone }}</div>@endif</td></tr>
                                <tr><th class="text-muted fw-normal">{{ __('recruitment.fields.camp_days') }}</th><td>{{ (int) $order->camp_days }}</td></tr>
                                <tr><th class="text-muted fw-normal">{{ __('recruitment.fields.camp_fee') }}</th><td>{{ number_format((float) $order->camp_fee, 2) }} {{ __('dashboard.sar') }}</td></tr>
                                <tr><th class="text-muted fw-normal">{{ __('recruitment.fields.delivery_fee') }}</th><td>{{ number_format((float) $order->delivery_fee, 2) }} {{ __('dashboard.sar') }}</td></tr>
                                <tr><th class="text-muted fw-normal">{{ __('recruitment.fields.fees_total') }}</th><td class="fw-semibold">{{ number_format($order->deliveryFeesTotal(), 2) }} {{ __('dashboard.sar') }}</td></tr>
                                <tr><th class="text-muted fw-normal">{{ __('recruitment.fields.delivered_by') }}</th><td>{{ $order->deliverer?->displayName() ?? '—' }}</td></tr>
                                <tr><th class="text-muted fw-normal">{{ __('recruitment.fields.probation_ends_at') }}</th><td dir="ltr" class="text-start">{{ $order->probation_ends_at?->format('Y-m-d') ?? '—' }}</td></tr>
                                @if ($order->completed_at)
                                    <tr><th class="text-muted fw-normal">{{ __('recruitment.fields.completed_at_probation') }}</th><td dir="ltr" class="text-start text-success">{{ $order->completed_at->format('Y-m-d') }}</td></tr>
                                @endif
                                @if ($order->returned_at)
                                    <tr><th class="text-muted fw-normal">{{ __('recruitment.fields.returned_at') }}</th><td class="text-danger"><span dir="ltr">{{ $order->returned_at->format('Y-m-d') }}</span> — {{ $order->returnReason?->trans('name') }}{{ $order->return_note ? ' — '.$order->return_note : '' }}</td></tr>
                                @endif
                            </tbody>
                        </table>
                        @if ($order->delivery_notes)
                            <p class="text-muted border-top pt-2 mt-2 mb-0" style="white-space: pre-line;">{{ $order->delivery_notes }}</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="col-xl-8">
            {{-- Progress --}}
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        @foreach ($steps as $step => $date)
                            @php
                                $stepEnum = RecruitmentStatus::from($step);
                                $reached = $order->status !== RecruitmentStatus::Cancelled
                                    && array_search($stepEnum, RecruitmentStatus::cases(), true) <= array_search($progressStatus, RecruitmentStatus::cases(), true);
                            @endphp
                            <div class="text-center flex-fill">
                                <div class="avatar-xs mx-auto mb-1">
                                    <span class="avatar-title rounded-circle {{ $reached ? 'bg-success' : 'bg-light text-muted' }}">
                                        <i class="{{ $reached ? 'ri-check-line' : 'ri-more-line' }}"></i>
                                    </span>
                                </div>
                                <div class="font-size-12 {{ $order->status === $stepEnum ? 'fw-bold' : 'text-muted' }}">{{ $stepEnum->label() }}</div>
                                <div class="font-size-11 text-muted" dir="ltr">{{ $date?->format('Y-m-d') }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Next step --}}
            @if ($canEdit && $order->status->isOpen())
                <div class="card border border-primary-subtle">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><i class="ri-arrow-right-circle-line me-1"></i>{{ __('recruitment.next_step') }}</h5>

                        @switch($order->status)
                            @case(RecruitmentStatus::New)
                                @if ($candidates->isEmpty())
                                    <div class="alert alert-warning mb-0">{{ __('recruitment.no_candidates') }}</div>
                                @else
                                    <form method="POST" action="{{ route('admin.recruitment.select', $order) }}" class="row g-2 align-items-end">
                                        @csrf
                                        <div class="col-md-9">
                                            <label class="form-label" for="worker_id">{{ __('recruitment.select_candidate') }}</label>
                                            <select name="worker_id" id="worker_id" class="form-select select2" required>
                                                <option value="">{{ __('common.select') }}</option>
                                                @foreach ($candidates as $candidate)
                                                    <option value="{{ $candidate->id }}">{{ $candidate->trans('name') }} — {{ __('workers.fields.experience_years') }}: {{ $candidate->experience_years }}</option>
                                                @endforeach
                                            </select>
                                            <div class="form-text">{{ __('recruitment.hints.candidates', ['count' => $candidates->count()]) }}</div>
                                        </div>
                                        <div class="col-md-3 d-grid">
                                            <button type="submit" class="btn btn-primary">{{ __('recruitment.actions.select') }}</button>
                                        </div>
                                    </form>
                                @endif
                                @break

                            @case(RecruitmentStatus::CandidateSelected)
                                    <form method="POST" action="{{ route('admin.recruitment.contract', $order) }}" class="row g-2 align-items-end">
                                        @csrf
                                        <div class="col-md-3">
                                            <label class="form-label" for="visa_number">{{ __('recruitment.fields.visa_number') }}</label>
                                            <input type="text" name="visa_number" id="visa_number" dir="ltr" maxlength="30" value="{{ old('visa_number') }}" required class="form-control @error('visa_number') is-invalid @enderror">
                                            @error('visa_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label" for="visa_issued_at">{{ __('recruitment.fields.visa_issued_at') }}</label>
                                            <input type="date" name="visa_issued_at" id="visa_issued_at" max="{{ $today }}" value="{{ old('visa_issued_at') }}" class="form-control @error('visa_issued_at') is-invalid @enderror">
                                            @error('visa_issued_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label" for="musaned_contract_no">{{ __('recruitment.fields.musaned_contract_no') }}</label>
                                            <input type="text" name="musaned_contract_no" id="musaned_contract_no" dir="ltr" value="{{ old('musaned_contract_no') }}" required class="form-control @error('musaned_contract_no') is-invalid @enderror">
                                            @error('musaned_contract_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label" for="contracted_at">{{ __('recruitment.fields.contracted_at') }}</label>
                                            <input type="date" name="contracted_at" id="contracted_at" max="{{ $today }}" value="{{ old('contracted_at', $today) }}" required class="form-control @error('contracted_at') is-invalid @enderror">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label" for="contract_amount">{{ __('recruitment.fields.contract_amount') }}</label>
                                            <input type="number" name="contract_amount" id="contract_amount" min="0" step="0.01" dir="ltr" value="{{ old('contract_amount', $order->contract_amount) }}" class="form-control">
                                        </div>
                                        <div class="col-md-3 d-grid">
                                            <button type="submit" class="btn btn-primary">{{ __('recruitment.actions.contract') }}</button>
                                        </div>
                                    </form>

                                <form method="POST" action="{{ route('admin.recruitment.release', $order) }}" class="row g-2 align-items-end mt-3 pt-3 border-top">
                                    @csrf
                                    <div class="col-md-9">
                                        <label class="form-label" for="release_reason">{{ __('recruitment.release_candidate') }}</label>
                                        <input type="text" name="reason" id="release_reason" maxlength="500" required placeholder="{{ __('recruitment.fields.reason') }}" class="form-control @error('reason') is-invalid @enderror">
                                        @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-3 d-grid">
                                        <button type="submit" class="btn btn-outline-warning">{{ __('recruitment.actions.release') }}</button>
                                    </div>
                                </form>
                                @break

                            @case(RecruitmentStatus::Contracted)
                                <form method="POST" action="{{ route('admin.recruitment.travel', $order) }}" class="row g-2 align-items-end">
                                    @csrf
                                    <div class="col-md-4">
                                        <label class="form-label" for="travel_date">{{ __('recruitment.fields.travel_started_at') }}</label>
                                        <input type="date" name="date" id="travel_date" max="{{ $today }}" value="{{ $today }}" required class="form-control">
                                    </div>
                                    <div class="col-md-5 text-muted font-size-13">{{ __('recruitment.hints.travel', ['count' => $order->country?->travelStages()->active()->count() ?? 0]) }}</div>
                                    <div class="col-md-3 d-grid">
                                        <button type="submit" class="btn btn-primary">{{ __('recruitment.actions.travel') }}</button>
                                    </div>
                                </form>
                                @break

                            @case(RecruitmentStatus::InTravel)
                                @if ($currentStage)
                                    <form method="POST" action="{{ route('admin.recruitment.stages.complete', [$order, $currentStage]) }}" class="row g-2 align-items-end">
                                        @csrf
                                        <div class="col-md-12 mb-1">
                                            {{ __('recruitment.current_stage') }}: <strong>{{ $currentStage->trans('name') }}</strong>
                                            @if ($currentStage->due_at)
                                                <span class="{{ $currentStage->isOverdue() ? 'text-danger' : 'text-muted' }} font-size-13">— {{ __('recruitment.due', ['date' => $currentStage->due_at->format('Y-m-d')]) }}</span>
                                            @endif
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label" for="stage_date">{{ __('recruitment.fields.completed_at') }}</label>
                                            <input type="date" name="date" id="stage_date" max="{{ $today }}" value="{{ $today }}" required class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="stage_notes">{{ __('recruitment.fields.notes') }}</label>
                                            <input type="text" name="notes" id="stage_notes" maxlength="500" class="form-control">
                                        </div>
                                        <div class="col-md-3 d-grid">
                                            <button type="submit" class="btn btn-primary">{{ __('recruitment.actions.complete_stage') }}</button>
                                        </div>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.recruitment.arrived', $order) }}" class="row g-2 align-items-end">
                                        @csrf
                                        <div class="col-md-3">
                                            <label class="form-label" for="arrived_date">{{ __('recruitment.fields.arrived_at') }}</label>
                                            <input type="date" name="date" id="arrived_date" max="{{ $today }}" value="{{ $today }}" required class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="camp_id">{{ __('recruitment.fields.arrival_camp_id') }}</label>
                                            <select name="camp_id" id="camp_id" class="form-select @error('camp_id') is-invalid @enderror">
                                                <option value="">{{ __('recruitment.no_camp') }}</option>
                                                @foreach ($camps as $camp)
                                                    <option value="{{ $camp->id }}" @selected((string) old('camp_id', $loop->first ? $camp->id : '') === (string) $camp->id) @disabled($camp->capacity && $camp->occupancy >= $camp->capacity)>
                                                        {{ $camp->trans('name') }} — {{ __('recruitment.camp_occupancy', ['count' => $camp->occupancy, 'capacity' => $camp->capacity ?: '∞']) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('camp_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            <div class="form-text">{{ __('recruitment.hints.arrived') }}</div>
                                        </div>
                                        <div class="col-md-3 d-grid">
                                            <button type="submit" class="btn btn-success">{{ __('recruitment.actions.arrived') }}</button>
                                        </div>
                                    </form>
                                @endif
                                @break

                            @case(RecruitmentStatus::Arrived)
                                @include('admin.recruitment._delivery-form')
                                @break

                            @case(RecruitmentStatus::Delivered)
                                @include('admin.recruitment._probation-actions')
                                @break
                        @endswitch

                        @if ($order->status->isCancellable())
                            <form method="POST" action="{{ route('admin.recruitment.cancel', $order) }}" class="row g-2 align-items-end mt-3 pt-3 border-top"
                                data-confirm="{{ __('recruitment.actions.cancel') }}?" data-confirm-text="{{ __('recruitment.confirm_cancel') }}">
                                @csrf
                                <div class="col-md-4">
                                    <label class="form-label" for="cancel_reason_id">{{ __('recruitment.fields.cancel_reason_id') }}</label>
                                    <select name="cancel_reason_id" id="cancel_reason_id" required class="form-select @error('cancel_reason_id') is-invalid @enderror">
                                        <option value="">{{ __('common.select') }}</option>
                                        @foreach ($cancelReasons as $reason)
                                            <option value="{{ $reason->id }}">{{ $reason->trans('name') }}</option>
                                        @endforeach
                                    </select>
                                    @error('cancel_reason_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label" for="cancel_note">{{ __('recruitment.fields.cancel_note') }}</label>
                                    <input type="text" name="cancel_note" id="cancel_note" maxlength="500" class="form-control">
                                </div>
                                <div class="col-md-3 d-grid">
                                    <button type="submit" class="btn btn-outline-danger">{{ __('recruitment.actions.cancel') }}</button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Travel stages --}}
            @if ($order->stages->isNotEmpty())
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">{{ __('recruitment.travel_stages') }}</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-centered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('reference.fields.name') }}</th>
                                        <th>{{ __('recruitment.fields.started_at') }}</th>
                                        <th>{{ __('recruitment.fields.due_at') }}</th>
                                        <th>{{ __('recruitment.fields.completed_at') }}</th>
                                        <th>{{ __('recruitment.fields.notes') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($order->stages as $stage)
                                        <tr @class(['table-danger' => $stage->isOverdue(), 'table-success' => $stage->completed_at !== null])>
                                            <td>{{ $loop->iteration }}</td>
                                            <td class="fw-semibold">{{ $stage->trans('name') }}</td>
                                            <td dir="ltr" class="text-start">{{ $stage->started_at?->format('Y-m-d') ?? '—' }}</td>
                                            <td dir="ltr" class="text-start">{{ $stage->due_at?->format('Y-m-d') ?? '—' }}</td>
                                            <td dir="ltr" class="text-start">
                                                {{ $stage->completed_at?->format('Y-m-d') ?? '—' }}
                                                @if ($stage->completer)
                                                    <div class="text-muted font-size-11">{{ $stage->completer->displayName() }}</div>
                                                @endif
                                            </td>
                                            <td>{{ $stage->notes ?? '' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
