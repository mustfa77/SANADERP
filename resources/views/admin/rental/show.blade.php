@extends('layouts.app')
@use('App\Enums\RentalContractStatus')

@section('title', $contract->number)

@section('content')
    @include('layouts.partials.page-title', ['title' => __('rental.show', ['number' => $contract->number]), 'parent' => __('rental.title')])

    <x-alerts :flash="false" />

    @php
        $canEdit = auth()->user()->can('update', $contract);
        $isActive = $contract->status === RentalContractStatus::Active;
        $left = $contract->daysLeft();
        $today = today()->toDateString();
    @endphp

    <div class="row">
        <div class="col-xl-5">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="mb-0"><code dir="ltr">{{ $contract->number }}</code></h5>
                        <span class="badge font-size-12 bg-{{ $contract->status->color() }}-subtle text-{{ $contract->status->color() }}">{{ $contract->status->label() }}</span>
                    </div>
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <th class="text-muted fw-normal" style="width: 40%;">{{ __('rental.fields.client_id') }}</th>
                                <td>
                                    @can('view', $contract->client)
                                        <a href="{{ route('admin.clients.show', $contract->client) }}">{{ $contract->client->trans('name') }}</a>
                                    @else
                                        {{ $contract->client?->trans('name') }}
                                    @endcan
                                    <div class="text-muted" dir="ltr">{{ $contract->client?->phone }}</div>
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted fw-normal">{{ __('rental.fields.worker_id') }}</th>
                                <td>
                                    <a href="{{ route('admin.workers.show', $contract->worker) }}">{{ $contract->worker->trans('name') }}</a>
                                    <div class="text-muted font-size-12">{{ $contract->worker->country?->trans('nationality') }} · {{ $contract->worker->profession?->trans('name') }}</div>
                                    <x-worker-status :status="$contract->worker->status" />
                                </td>
                            </tr>
                            <tr><th class="text-muted fw-normal">{{ __('rental.fields.branch_id') }}</th><td>{{ $contract->branch?->trans('name') }}</td></tr>
                            <tr>
                                <th class="text-muted fw-normal">{{ __('rental.fields.period') }}</th>
                                <td>
                                    <span dir="ltr">{{ $contract->starts_at->format('Y-m-d') }} → {{ $contract->ends_at->format('Y-m-d') }}</span>
                                    @if ($isActive)
                                        <div class="font-size-12 {{ $left < 0 ? 'text-danger' : 'text-muted' }}">{{ $left < 0 ? __('rental.overdue_days', ['count' => abs($left)]) : __('rental.days_left', ['count' => $left]) }}</div>
                                    @endif
                                </td>
                            </tr>
                            <tr><th class="text-muted fw-normal">{{ __('rental.fields.price') }}</th><td>{{ number_format((float) $contract->price, 2) }}</td></tr>
                            @if ((float) $contract->discount > 0)
                                <tr><th class="text-muted fw-normal">{{ __('rental.fields.discount') }}</th><td class="text-danger">− {{ number_format((float) $contract->discount, 2) }}</td></tr>
                            @endif
                            <tr><th class="text-muted fw-normal">{{ __('rental.fields.vat_amount') }} ({{ (float) $contract->vat_percent }}%)</th><td>{{ number_format((float) $contract->vat_amount, 2) }}</td></tr>
                            <tr><th class="text-muted fw-normal">{{ __('rental.fields.total') }}</th><td class="fw-bold">{{ number_format((float) $contract->total, 2) }} {{ __('dashboard.sar') }}</td></tr>
                            @if ($contract->final_total !== null)
                                <tr><th class="text-muted fw-normal">{{ __('rental.fields.final_total') }}</th><td class="fw-bold text-primary">{{ number_format((float) $contract->final_total, 2) }} {{ __('dashboard.sar') }}</td></tr>
                            @endif
                            @if ($contract->renewedFrom)
                                <tr><th class="text-muted fw-normal">{{ __('rental.renewed_from') }}</th><td><a href="{{ route('admin.rental.show', $contract->renewedFrom) }}" dir="ltr">{{ $contract->renewedFrom->number }}</a></td></tr>
                            @endif
                            @if ($contract->renewal)
                                <tr><th class="text-muted fw-normal">{{ __('rental.renewed_by') }}</th><td><a href="{{ route('admin.rental.show', $contract->renewal) }}" dir="ltr">{{ $contract->renewal->number }}</a></td></tr>
                            @endif
                            @if ($contract->closed_at)
                                <tr>
                                    <th class="text-muted fw-normal">{{ __('rental.fields.closed_at') }}</th>
                                    <td>
                                        <span dir="ltr">{{ $contract->closed_at->format('Y-m-d') }}</span>
                                        {{ $contract->closeReason ? ' — '.$contract->closeReason->trans('name') : '' }}{{ $contract->close_note ? ' — '.$contract->close_note : '' }}
                                        @if ($contract->returnCamp)
                                            <div class="text-muted font-size-12">{{ __('rental.fields.camp_id') }}: {{ $contract->returnCamp->trans('name') }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                            <tr><th class="text-muted fw-normal">{{ __('rental.fields.created_by') }}</th><td>{{ $contract->creator?->displayName() ?? '—' }}</td></tr>
                        </tbody>
                    </table>
                    @if ($contract->notes)
                        <p class="text-muted border-top pt-2 mt-2 mb-0" style="white-space: pre-line;">{{ $contract->notes }}</p>
                    @endif
                    @can('print', $contract)
                        <div class="border-top pt-3 mt-3">
                            <a href="{{ route('admin.rental.pdf', $contract) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary w-100"><i class="ri-printer-line me-1"></i>{{ __('rental.actions.print') }}</a>
                        </div>
                    @endcan
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            @if ($canEdit && $isActive)
                <div class="card border border-primary-subtle">
                    <div class="card-body">
                        {{-- Renew for the next month --}}
                        <h5 class="card-title mb-2"><i class="ri-refresh-line me-1"></i>{{ __('rental.renew_title') }}</h5>
                        <p class="text-muted font-size-13">{{ __('rental.hints.renew', ['start' => $contract->ends_at->copy()->addDay()->format('Y-m-d')]) }}</p>
                        <form method="POST" action="{{ route('admin.rental.renew', $contract) }}" class="row g-2 align-items-end"
                            data-confirm="{{ __('rental.actions.renew') }}?" data-confirm-text="{{ __('rental.confirm_renew') }}">
                            @csrf
                            <div class="col-md-4">
                                <label class="form-label" for="renew_price">{{ __('rental.fields.price') }}</label>
                                <input type="number" name="price" id="renew_price" step="0.01" min="0" dir="ltr" value="{{ old('price', $renewalQuote['price'] ?? $contract->price) }}" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="renew_discount">{{ __('rental.fields.discount') }}</label>
                                <input type="number" name="discount" id="renew_discount" step="0.01" min="0" dir="ltr" value="{{ old('discount', 0) }}" class="form-control">
                            </div>
                            <div class="col-md-4 d-grid">
                                <button type="submit" class="btn btn-primary"><i class="ri-refresh-line me-1"></i>{{ __('rental.actions.renew') }}</button>
                            </div>
                        </form>

                        {{-- Return / replace / cancel --}}
                        @include('admin.rental._return-actions')
                    </div>
                </div>
            @endif

            {{-- Workers of the contract: departure and return with date and time --}}
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="ri-history-line me-1"></i>{{ __('rental.assignments.title') }}</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-centered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('rental.fields.worker_id') }}</th>
                                    <th>{{ __('rental.fields.departed_at') }}</th>
                                    <th>{{ __('rental.fields.returned_at') }}</th>
                                    <th>{{ __('rental.fields.close_reason_id') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($contract->assignments as $assignment)
                                    <tr>
                                        <td><a href="{{ route('admin.workers.show', $assignment->worker) }}">{{ $assignment->worker?->trans('name') }}</a></td>
                                        <td dir="ltr" class="text-start">{{ $assignment->departed_at->format('Y-m-d H:i') }}</td>
                                        <td dir="ltr" class="text-start">
                                            @if ($assignment->returned_at)
                                                {{ $assignment->returned_at->format('Y-m-d H:i') }}
                                            @else
                                                <span class="badge bg-success-subtle text-success">{{ __('rental.assignments.with_client') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $assignment->returnReason?->trans('name') }}{{ $assignment->return_note ? ' — '.$assignment->return_note : '' }}
                                            @if ($assignment->returnCamp)
                                                <div class="text-muted font-size-12">{{ __('rental.fields.camp_id') }}: {{ $assignment->returnCamp->trans('name') }}</div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">—</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @include('admin.payments._panel', ['payable' => $contract, 'payableType' => 'rental', 'canPay' => true])
        </div>
    </div>
@endsection
