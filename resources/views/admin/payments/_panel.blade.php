{{-- Payments of a contract / booking: totals, list (receipt, verify, delete) and the "record payment" form.
     Expects $payable (RentalContract|HourlyBooking with payments loaded), $payableType ('rental'|'hourly') and $canPay. --}}
@php
    $paid = $payable->paidAmount();
    $balance = $payable->balance();
    $status = $payable->paymentStatus();
    $methods = \App\Models\Lookup::query()->ofType(\App\Enums\LookupType::PaymentMethod)->active()->ordered()->get();
@endphp
<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h5 class="card-title mb-0"><i class="ri-money-dollar-circle-line me-1"></i>{{ __('payments.title') }}</h5>
            <span class="badge font-size-12 bg-{{ $status->color() }}-subtle text-{{ $status->color() }}">{{ $status->label() }}</span>
        </div>

        <div class="row text-center mb-3 g-2">
            @foreach (['total' => $payable->dueTotal(), 'paid' => $paid, 'balance' => $balance] as $key => $amount)
                <div class="col-4">
                    <div class="border rounded p-2">
                        <div class="text-muted font-size-12">{{ __('payments.summary.'.$key) }}</div>
                        <div class="fw-semibold {{ $key === 'balance' && $amount > 0 ? 'text-danger' : '' }}">{{ number_format($amount, 2) }} <span class="font-size-11">{{ __('dashboard.sar') }}</span></div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($payable->payments->isNotEmpty())
            <div class="table-responsive mb-3">
                <table class="table table-sm table-centered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('payments.fields.paid_at') }}</th>
                            <th>{{ __('payments.fields.amount') }}</th>
                            <th>{{ __('payments.fields.payment_method_id') }}</th>
                            <th>{{ __('payments.fields.receipt') }}</th>
                            <th>{{ __('payments.verified') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payable->payments as $payment)
                            <tr>
                                <td dir="ltr" class="text-start">{{ $payment->paid_at->format('Y-m-d') }}</td>
                                <td class="fw-semibold">{{ number_format((float) $payment->amount, 2) }}</td>
                                <td>
                                    {{ $payment->method?->trans('name') }}
                                    @if ($payment->reference)
                                        <div class="text-muted font-size-11" dir="ltr">{{ $payment->reference }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($payment->receipt_path)
                                        <a href="{{ route('admin.payments.receipt', $payment) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary" title="{{ $payment->receipt_name }}">
                                            <i class="ri-attachment-2"></i> {{ __('payments.view_receipt') }}
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($payment->isVerified())
                                        <span class="text-success" title="{{ $payment->verifier?->displayName() }} · {{ $payment->verified_at->format('Y-m-d H:i') }}"><i class="ri-checkbox-circle-fill"></i></span>
                                    @else
                                        @can('verify', $payment)
                                            <form method="POST" action="{{ route('admin.payments.verify', $payment) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success">{{ __('payments.actions.verify') }}</button>
                                            </form>
                                        @else
                                            <span class="text-warning font-size-12">{{ __('payments.pending') }}</span>
                                        @endcan
                                    @endif
                                </td>
                                <td class="text-end">
                                    @can('delete', $payment)
                                        <form method="POST" action="{{ route('admin.payments.destroy', $payment) }}" class="d-inline"
                                            data-confirm="{{ __('payments.actions.delete') }}?" data-confirm-text="{{ __('payments.confirm_delete') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('payments.actions.delete') }}"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                            @if ($payment->notes)
                                <tr><td colspan="6" class="text-muted font-size-12 border-top-0 pt-0">{{ $payment->notes }} — {{ $payment->receiver?->displayName() }}</td></tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($payable->relationLoaded('refunds') && $payable->refunds->isNotEmpty())
            <h6 class="mt-2 mb-2"><i class="ri-refund-2-line me-1"></i>{{ __('refunds.title') }}</h6>
            @include('admin.refunds._table', ['refunds' => $payable->refunds, 'showPayable' => false])
        @endif

        @if ($canPay && $balance > 0)
            @can('create', App\Models\Payment::class)
                <form method="POST" action="{{ route('admin.payments.store', [$payableType, $payable->id]) }}" enctype="multipart/form-data" class="row g-2 border-top pt-3" novalidate>
                    @csrf
                    <div class="col-12 fw-semibold">{{ __('payments.record') }}</div>
                    <div class="col-md-4">
                        <label class="form-label" for="amount">{{ __('payments.fields.amount') }}</label>
                        <input type="number" name="amount" id="amount" step="0.01" min="0.01" max="{{ $balance }}" dir="ltr" value="{{ old('amount', number_format($balance, 2, '.', '')) }}" required class="form-control @error('amount') is-invalid @enderror">
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="paid_at">{{ __('payments.fields.paid_at') }}</label>
                        <input type="date" name="paid_at" id="paid_at" max="{{ today()->toDateString() }}" value="{{ old('paid_at', today()->toDateString()) }}" required class="form-control @error('paid_at') is-invalid @enderror">
                        @error('paid_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="payment_method_id">{{ __('payments.fields.payment_method_id') }}</label>
                        <select name="payment_method_id" id="payment_method_id" required class="form-select @error('payment_method_id') is-invalid @enderror">
                            <option value="">{{ __('common.select') }}</option>
                            @foreach ($methods as $method)
                                <option value="{{ $method->id }}" @selected((string) old('payment_method_id') === (string) $method->id)>{{ $method->trans('name') }}</option>
                            @endforeach
                        </select>
                        @error('payment_method_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="receipt">{{ __('payments.fields.receipt') }}</label>
                        <input type="file" name="receipt" id="receipt" accept=".pdf,.jpg,.jpeg,.png" class="form-control @error('receipt') is-invalid @enderror">
                        <div class="form-text">{{ __('payments.hints.receipt') }}</div>
                        @error('receipt') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="reference">{{ __('payments.fields.reference') }}</label>
                        <input type="text" name="reference" id="reference" dir="ltr" maxlength="100" value="{{ old('reference') }}" class="form-control">
                    </div>
                    <div class="col-md-9">
                        <label class="form-label" for="payment_notes">{{ __('payments.fields.notes') }}</label>
                        <input type="text" name="notes" id="payment_notes" maxlength="1000" value="{{ old('notes') }}" class="form-control">
                    </div>
                    <div class="col-md-3 d-grid align-items-end">
                        <button type="submit" class="btn btn-success mt-md-4"><i class="ri-add-line me-1"></i>{{ __('payments.actions.record') }}</button>
                    </div>
                </form>
            @endcan
        @endif
    </div>
</div>
