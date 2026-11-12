@extends('layouts.app')
@use('App\Enums\CaseDecision')
@use('App\Enums\CaseStatus')

@section('title', $case->number)

@section('content')
    @include('layouts.partials.page-title', ['title' => __('cases.show', ['number' => $case->number]), 'parent' => __('cases.title')])

    <x-alerts :flash="false" />

    @php
        $worker = $case->worker;
        $related = $case->related;
        $relatedUrl = match (true) {
            $related instanceof App\Models\RecruitmentOrder => route('admin.recruitment.show', $related),
            $related instanceof App\Models\RentalContract => route('admin.rental.show', $related),
            default => null,
        };
    @endphp

    <div class="row">
        <div class="col-xl-5">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><code dir="ltr">{{ $case->number }}</code></h5>
                        <span class="badge font-size-12 bg-{{ $case->status->color() }}-subtle text-{{ $case->status->color() }}">{{ $case->status->label() }}</span>
                    </div>
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <th class="text-muted fw-normal" style="width: 40%;">{{ __('cases.fields.worker_id') }}</th>
                                <td>
                                    <a href="{{ route('admin.workers.show', $worker) }}" class="fw-semibold">{{ $worker->trans('name') }}</a>
                                    <div class="text-muted font-size-12">{{ $worker->country?->trans('nationality') }} · {{ $worker->profession?->trans('name') }} · {{ $worker->sponsor_type?->label() }}</div>
                                    <x-worker-status :status="$worker->status" />
                                </td>
                            </tr>
                            <tr><th class="text-muted fw-normal">{{ __('camps.current') }}</th><td>{{ $worker->camp?->trans('name') ?? '—' }}</td></tr>
                            <tr><th class="text-muted fw-normal">{{ __('cases.fields.reason') }}</th><td>{{ $case->reason ?? '—' }}</td></tr>
                            @if ($relatedUrl)
                                <tr><th class="text-muted fw-normal">{{ __('cases.fields.related') }}</th><td><a href="{{ $relatedUrl }}" dir="ltr">{{ $related->number }}</a></td></tr>
                            @endif
                            <tr><th class="text-muted fw-normal">{{ __('cases.fields.opened_at') }}</th><td><span dir="ltr">{{ $case->opened_at->format('Y-m-d H:i') }}</span> · {{ __('cases.days', ['count' => $case->ageInDays()]) }}</td></tr>
                            @if ($case->decision)
                                <tr>
                                    <th class="text-muted fw-normal">{{ __('cases.fields.decision') }}</th>
                                    <td class="fw-semibold">{{ $case->decision->label() }}
                                        <div class="text-muted font-size-12 fw-normal">{{ $case->decider?->displayName() }} · <span dir="ltr">{{ $case->decided_at?->format('Y-m-d') }}</span></div>
                                        @if ($case->decision_note)<div class="font-size-13 fw-normal">{{ $case->decision_note }}</div>@endif
                                    </td>
                                </tr>
                            @endif
                            @if ($case->decision === CaseDecision::Transfer)
                                <tr><th class="text-muted fw-normal">{{ __('cases.fields.new_sponsor') }}</th><td>{{ $case->new_sponsor_name ?? $case->newSponsor?->trans('name') }} <span dir="ltr" class="text-muted">{{ $case->new_sponsor_id_number }}</span></td></tr>
                                <tr><th class="text-muted fw-normal">{{ __('cases.fields.transfer_fee') }}</th><td>{{ $case->transfer_fee !== null ? number_format((float) $case->transfer_fee, 2).' '.__('dashboard.sar') : '—' }}</td></tr>
                            @endif
                            @if ($case->decision === CaseDecision::Deportation)
                                <tr><th class="text-muted fw-normal">{{ __('cases.fields.flight_date') }}</th><td dir="ltr" class="text-start">{{ $case->flight_date?->format('Y-m-d') ?? '—' }}</td></tr>
                                <tr><th class="text-muted fw-normal">{{ __('cases.fields.ticket_cost') }}</th><td>{{ $case->ticket_cost !== null ? number_format((float) $case->ticket_cost, 2).' '.__('dashboard.sar') : '—' }}</td></tr>
                            @endif
                            @if ($case->completed_at)
                                <tr><th class="text-muted fw-normal">{{ __('cases.fields.completed_at') }}</th><td><span dir="ltr">{{ $case->completed_at->format('Y-m-d') }}</span> · {{ $case->completer?->displayName() }}</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">{{ __('workers.tabs.status_history') }}</h5>
                    <ul class="list-unstyled mb-0 font-size-13">
                        @foreach ($history as $row)
                            <li class="border-bottom py-1">
                                <span dir="ltr" class="text-muted">{{ $row->changed_at?->format('Y-m-d') }}</span>
                                {{ $row->to_status?->label() }}{{ $row->reason ? ' — '.$row->reason : '' }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            {{-- Decision --}}
            @can('decide', $case)
                <div class="card border border-primary-subtle">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><i class="ri-scales-3-line me-1"></i>{{ __('cases.decide_title') }}</h5>
                        <form method="POST" action="{{ route('admin.cases.decide', $case) }}" class="row g-2" id="decide-form"
                            data-confirm="{{ __('cases.decide_title') }}?" data-confirm-text="{{ __('cases.confirm_decide') }}">
                            @csrf
                            <div class="col-12">
                                @foreach ($decisions as $decision)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="decision" id="decision_{{ $decision->value }}" value="{{ $decision->value }}" @checked(old('decision', 'return_to_work') === $decision->value)>
                                        <label class="form-check-label" for="decision_{{ $decision->value }}">{{ $decision->label() }}</label>
                                    </div>
                                @endforeach
                                @error('decision') <div class="text-danger font-size-12">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12 decision-box" data-decision="transfer">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label" for="new_sponsor_client_id">{{ __('cases.fields.new_sponsor_client_id') }}</label>
                                        <select name="new_sponsor_client_id" id="new_sponsor_client_id" class="form-select select2">
                                            <option value="">{{ __('cases.other_sponsor') }}</option>
                                            @foreach ($clients as $client)
                                                <option value="{{ $client->id }}" @selected((string) old('new_sponsor_client_id') === (string) $client->id)>{{ $client->trans('name') }} — {{ $client->phone }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="new_sponsor_name">{{ __('cases.fields.new_sponsor_name') }}</label>
                                        <input type="text" name="new_sponsor_name" id="new_sponsor_name" maxlength="255" value="{{ old('new_sponsor_name') }}" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="new_sponsor_id_number">{{ __('cases.fields.new_sponsor_id_number') }}</label>
                                        <input type="text" name="new_sponsor_id_number" id="new_sponsor_id_number" maxlength="20" dir="ltr" value="{{ old('new_sponsor_id_number') }}" class="form-control @error('new_sponsor_id_number') is-invalid @enderror">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="transfer_fee">{{ __('cases.fields.transfer_fee') }}</label>
                                        <input type="number" name="transfer_fee" id="transfer_fee" step="0.01" min="0" dir="ltr" value="{{ old('transfer_fee') }}" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 decision-box" data-decision="deportation">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label" for="flight_date">{{ __('cases.fields.flight_date') }}</label>
                                        <input type="date" name="flight_date" id="flight_date" value="{{ old('flight_date') }}" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="ticket_cost">{{ __('cases.fields.ticket_cost') }}</label>
                                        <input type="number" name="ticket_cost" id="ticket_cost" step="0.01" min="0" dir="ltr" value="{{ old('ticket_cost') }}" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="decision_note">{{ __('cases.fields.decision_note') }}</label>
                                <textarea name="decision_note" id="decision_note" rows="2" maxlength="1000" class="form-control">{{ old('decision_note') }}</textarea>
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary"><i class="ri-check-line me-1"></i>{{ __('cases.actions.decide') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            @elseif ($case->status === CaseStatus::Open)
                <div class="alert alert-warning">{{ __('cases.waiting_decision') }}</div>
            @endif

            {{-- Complete a decided transfer / deportation --}}
            @if ($case->status === CaseStatus::InProgress)
                @can('update', $case)
                    <div class="card border border-success-subtle">
                        <div class="card-body">
                            <h5 class="card-title mb-2"><i class="ri-flag-line me-1"></i>{{ __('cases.complete_title.'.$case->decision->value) }}</h5>
                            <p class="text-muted font-size-13">{{ __('cases.complete_hint.'.$case->decision->value) }}</p>
                            <form method="POST" action="{{ route('admin.cases.complete', $case) }}" class="row g-2 align-items-end"
                                data-confirm="{{ __('cases.complete_title.'.$case->decision->value) }}?" data-confirm-text="{{ __('cases.confirm_complete') }}">
                                @csrf
                                <div class="col-md-4">
                                    <label class="form-label" for="completed_at">{{ __('cases.fields.completed_at') }}</label>
                                    <input type="date" name="completed_at" id="completed_at" max="{{ today()->toDateString() }}" value="{{ old('completed_at', today()->toDateString()) }}" required class="form-control @error('completed_at') is-invalid @enderror">
                                </div>
                                @if ($case->decision === CaseDecision::Deportation)
                                    <div class="col-md-4">
                                        <label class="form-label" for="complete_flight_date">{{ __('cases.fields.flight_date') }}</label>
                                        <input type="date" name="flight_date" id="complete_flight_date" value="{{ old('flight_date', $case->flight_date?->toDateString()) }}" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="complete_ticket_cost">{{ __('cases.fields.ticket_cost') }}</label>
                                        <input type="number" name="ticket_cost" id="complete_ticket_cost" step="0.01" min="0" dir="ltr" value="{{ old('ticket_cost', $case->ticket_cost) }}" class="form-control">
                                    </div>
                                @else
                                    <div class="col-md-4">
                                        <label class="form-label" for="complete_transfer_fee">{{ __('cases.fields.transfer_fee') }}</label>
                                        <input type="number" name="transfer_fee" id="complete_transfer_fee" step="0.01" min="0" dir="ltr" value="{{ old('transfer_fee', $case->transfer_fee) }}" class="form-control">
                                    </div>
                                @endif
                                <div class="col-md-8">
                                    <label class="form-label" for="complete_note">{{ __('cases.fields.note') }}</label>
                                    <input type="text" name="note" id="complete_note" maxlength="2000" class="form-control">
                                </div>
                                <div class="col-md-4 d-grid">
                                    <button type="submit" class="btn btn-success"><i class="ri-flag-line me-1"></i>{{ __('cases.actions.complete') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endcan
            @endif

            {{-- Follow-up notes --}}
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="ri-chat-3-line me-1"></i>{{ __('cases.notes') }}</h5>
                    @can('update', $case)
                        <form method="POST" action="{{ route('admin.cases.note', $case) }}" class="d-flex gap-2 mb-3">
                            @csrf
                            <input type="text" name="note" maxlength="2000" required class="form-control @error('note') is-invalid @enderror" placeholder="{{ __('cases.add_note') }}">
                            <button type="submit" class="btn btn-outline-primary text-nowrap">{{ __('cases.actions.add_note') }}</button>
                        </form>
                    @endcan
                    @forelse ($case->notes as $note)
                        <div class="border-bottom py-2">
                            <div class="font-size-13" style="white-space: pre-line;">{{ $note->note }}</div>
                            <div class="text-muted font-size-11">{{ $note->author?->displayName() }} · <span dir="ltr">{{ $note->created_at->format('Y-m-d H:i') }}</span></div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">{{ __('cases.no_notes') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var form = document.getElementById('decide-form');
            if (! form) {
                return;
            }
            function toggle() {
                var checked = form.querySelector('input[name="decision"]:checked');
                form.querySelectorAll('.decision-box').forEach(function (box) {
                    box.classList.toggle('d-none', ! checked || box.dataset.decision !== checked.value);
                });
            }
            form.querySelectorAll('input[name="decision"]').forEach(function (radio) { radio.addEventListener('change', toggle); });
            toggle();
        })();
    </script>
@endpush
