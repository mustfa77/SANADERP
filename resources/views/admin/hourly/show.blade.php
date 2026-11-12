@extends('layouts.app')
@use('App\Enums\HourlyBookingStatus')

@section('title', $booking->number)

@section('content')
    @include('layouts.partials.page-title', ['title' => __('hourly.show', ['number' => $booking->number]), 'parent' => __('hourly.title')])

    <x-alerts :flash="false" />

    @php
        $canEdit = auth()->user()->can('update', $booking);
        $isBooked = $booking->status === HourlyBookingStatus::Booked;
    @endphp

    <div class="row">
        <div class="col-xl-5">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="mb-0"><code dir="ltr">{{ $booking->number }}</code></h5>
                        <span class="badge font-size-12 bg-{{ $booking->status->color() }}-subtle text-{{ $booking->status->color() }}">{{ $booking->status->label() }}</span>
                    </div>
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <th class="text-muted fw-normal" style="width: 40%;">{{ __('hourly.fields.client_id') }}</th>
                                <td>
                                    @can('view', $booking->client)
                                        <a href="{{ route('admin.clients.show', $booking->client) }}">{{ $booking->client->trans('name') }}</a>
                                    @else
                                        {{ $booking->client?->trans('name') }}
                                    @endcan
                                    <div class="text-muted" dir="ltr">{{ $booking->client?->phone }}</div>
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted fw-normal">{{ __('hourly.fields.worker_id') }}</th>
                                <td><a href="{{ route('admin.workers.show', $booking->worker) }}">{{ $booking->worker->trans('name') }}</a> <span class="text-muted font-size-12">{{ $booking->worker->country?->trans('nationality') }}</span></td>
                            </tr>
                            <tr><th class="text-muted fw-normal">{{ __('hourly.fields.date') }}</th><td dir="ltr" class="text-start">{{ $booking->date->format('Y-m-d') }}</td></tr>
                            <tr>
                                <th class="text-muted fw-normal">{{ __('hourly.fields.shift_id') }}</th>
                                <td>{{ $booking->shift?->trans('name') }} — <span dir="ltr">{{ $booking->starts_at->format('H:i') }} – {{ $booking->ends_at->format('H:i') }}</span> ({{ (float) $booking->hours }} {{ __('hourly.hours') }})</td>
                            </tr>
                            @if ($booking->shift?->move_time)
                                <tr><th class="text-muted fw-normal">{{ __('hourly.move_time') }}</th><td dir="ltr" class="text-start">{{ substr($booking->shift->move_time, 0, 5) }}</td></tr>
                            @endif
                            @if ($booking->address)
                                <tr><th class="text-muted fw-normal">{{ __('hourly.fields.address') }}</th><td>{{ $booking->address }}</td></tr>
                            @endif
                            <tr><th class="text-muted fw-normal">{{ __('hourly.fields.hourly_price') }}</th><td>{{ number_format((float) $booking->hourly_price, 2) }} × {{ (float) $booking->hours }} = {{ number_format((float) $booking->price, 2) }}</td></tr>
                            @if ((float) $booking->discount > 0)
                                <tr><th class="text-muted fw-normal">{{ __('hourly.fields.discount') }}</th><td class="text-danger">− {{ number_format((float) $booking->discount, 2) }}</td></tr>
                            @endif
                            <tr><th class="text-muted fw-normal">{{ __('hourly.fields.vat_amount') }} ({{ (float) $booking->vat_percent }}%)</th><td>{{ number_format((float) $booking->vat_amount, 2) }}</td></tr>
                            <tr><th class="text-muted fw-normal">{{ __('hourly.fields.total') }}</th><td class="fw-bold">{{ number_format((float) $booking->total, 2) }} {{ __('dashboard.sar') }}</td></tr>
                            <tr>
                                <th class="text-muted fw-normal">{{ __('hourly.fields.departed_at') }}</th>
                                <td dir="ltr" class="text-start">{{ $booking->departed_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted fw-normal">{{ __('hourly.fields.returned_at') }}</th>
                                <td>
                                    <span dir="ltr">{{ $booking->returned_at?->format('Y-m-d H:i') ?? '—' }}</span>
                                    @if ($booking->lateMinutes() > 0)
                                        <span class="badge bg-danger-subtle text-danger">{{ __('hourly.late', ['minutes' => $booking->lateMinutes()]) }}</span>
                                    @endif
                                </td>
                            </tr>
                            @if ($booking->status === HourlyBookingStatus::Cancelled)
                                <tr><th class="text-muted fw-normal">{{ __('hourly.fields.cancel_reason_id') }}</th><td class="text-danger">{{ $booking->cancelReason?->trans('name') }}{{ $booking->cancel_note ? ' — '.$booking->cancel_note : '' }}</td></tr>
                            @endif
                            <tr><th class="text-muted fw-normal">{{ __('hourly.fields.created_by') }}</th><td>{{ $booking->creator?->displayName() ?? '—' }}</td></tr>
                        </tbody>
                    </table>
                    @if ($booking->notes)
                        <p class="text-muted border-top pt-2 mt-2 mb-0" style="white-space: pre-line;">{{ $booking->notes }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            @if ($canEdit && $isBooked)
                <div class="card border border-primary-subtle">
                    <div class="card-body">
                        @php
                            $nowLocal = now()->format('Y-m-d\TH:i');
                        @endphp
                        <div class="row g-3">
                            {{-- Departure --}}
                            <div class="col-md-6">
                                <form method="POST" action="{{ route('admin.hourly.depart', $booking) }}" class="border rounded p-2 h-100">
                                    @csrf
                                    <label class="form-label fw-semibold" for="departed_at"><i class="ri-logout-circle-r-line me-1"></i>{{ __('hourly.fields.departed_at') }}</label>
                                    <input type="datetime-local" name="departed_at" id="departed_at" max="{{ $nowLocal }}" value="{{ old('departed_at', $booking->departed_at?->format('Y-m-d\TH:i') ?? $nowLocal) }}" required class="form-control mb-2 @error('departed_at') is-invalid @enderror">
                                    @error('departed_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <button type="submit" class="btn btn-outline-primary w-100" @disabled($booking->starts_at->copy()->startOfDay()->isFuture())>
                                        {{ $booking->departed_at ? __('hourly.actions.update_departure') : __('hourly.actions.depart') }}
                                    </button>
                                </form>
                            </div>
                            {{-- Return = completed --}}
                            <div class="col-md-6">
                                <form method="POST" action="{{ route('admin.hourly.complete', $booking) }}" class="border rounded p-2 h-100">
                                    @csrf
                                    <label class="form-label fw-semibold" for="returned_at"><i class="ri-login-circle-line me-1"></i>{{ __('hourly.fields.returned_at') }}</label>
                                    <input type="datetime-local" name="returned_at" id="returned_at" max="{{ $nowLocal }}" value="{{ old('returned_at', $nowLocal) }}" required class="form-control mb-2 @error('returned_at') is-invalid @enderror">
                                    @error('returned_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <button type="submit" class="btn btn-success w-100" @disabled($booking->starts_at->isFuture())><i class="ri-check-double-line me-1"></i>{{ __('hourly.actions.complete') }}</button>
                                </form>
                            </div>
                        </div>
                        <div class="text-muted font-size-12 mt-2">{{ __('hourly.hints.complete') }}</div>

                        <form method="POST" action="{{ route('admin.hourly.cancel', $booking) }}" class="row g-2 align-items-end mt-3 pt-3 border-top"
                            data-confirm="{{ __('hourly.actions.cancel') }}?" data-confirm-text="{{ __('hourly.confirm_cancel') }}">
                            @csrf
                            <div class="col-md-4">
                                <label class="form-label" for="cancel_reason_id">{{ __('hourly.fields.cancel_reason_id') }}</label>
                                <select name="cancel_reason_id" id="cancel_reason_id" required class="form-select @error('cancel_reason_id') is-invalid @enderror">
                                    <option value="">{{ __('common.select') }}</option>
                                    @foreach ($cancelReasons as $reason)
                                        <option value="{{ $reason->id }}">{{ $reason->trans('name') }}</option>
                                    @endforeach
                                </select>
                                @error('cancel_reason_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="cancel_note">{{ __('hourly.fields.cancel_note') }}</label>
                                <input type="text" name="cancel_note" id="cancel_note" maxlength="500" class="form-control">
                            </div>
                            @if ($booking->paidAmount() > 0)
                                <div class="col-md-4">
                                    <label class="form-label" for="cancel_refund">{{ __('refunds.fields.amount') }}</label>
                                    <input type="number" name="refund" id="cancel_refund" step="0.01" min="0" max="{{ $booking->paidAmount() }}" dir="ltr" value="{{ number_format($booking->paidAmount(), 2, '.', '') }}" class="form-control">
                                    <div class="form-text">{{ __('hourly.hints.cancel_refund') }}</div>
                                </div>
                            @endif
                            <div class="col-md-3 d-grid">
                                <button type="submit" class="btn btn-outline-danger">{{ __('hourly.actions.cancel') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            @include('admin.payments._panel', ['payable' => $booking, 'payableType' => 'hourly', 'canPay' => $booking->status !== HourlyBookingStatus::Cancelled])
        </div>
    </div>
@endsection
