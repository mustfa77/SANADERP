{{-- Probation after delivery (order "delivered"): complete it, or record a return / absconding. --}}
@php
    $daysLeft = $order->probation_ends_at ? (int) today()->diffInDays($order->probation_ends_at, false) : null;
@endphp
<div class="alert {{ $daysLeft !== null && $daysLeft <= 0 ? 'alert-warning' : 'alert-info' }} py-2">
    <i class="ri-timer-line me-1"></i>
    {{ __('recruitment.probation_until', ['date' => $order->probation_ends_at?->format('Y-m-d') ?? '—']) }}
    @if ($daysLeft !== null)
        — {{ $daysLeft > 0 ? __('recruitment.days_left', ['count' => $daysLeft]) : __('recruitment.probation_ended') }}
    @endif
</div>

<form method="POST" action="{{ route('admin.recruitment.probation.complete', $order) }}" class="row g-2 align-items-end"
    data-confirm="{{ __('recruitment.actions.complete_probation') }}?" data-confirm-text="{{ __('recruitment.confirm_complete') }}">
    @csrf
    <div class="col-md-4">
        <label class="form-label" for="completed_date">{{ __('recruitment.fields.completed_at_probation') }}</label>
        <input type="date" name="date" id="completed_date" min="{{ $order->delivered_at?->toDateString() }}" max="{{ $today }}" value="{{ $today }}" required class="form-control">
    </div>
    <div class="col-md-5 text-muted font-size-13">{{ __('recruitment.hints.complete_probation') }}</div>
    <div class="col-md-3 d-grid">
        <button type="submit" class="btn btn-success">{{ __('recruitment.actions.complete_probation') }}</button>
    </div>
</form>

<form method="POST" action="{{ route('admin.recruitment.probation.return', $order) }}" class="row g-2 align-items-end mt-3 pt-3 border-top"
    data-confirm="{{ __('recruitment.actions.return') }}?" data-confirm-text="{{ __('recruitment.confirm_return') }}">
    @csrf
    <div class="col-12 fw-semibold">{{ __('recruitment.return_title') }}</div>
    <div class="col-md-3">
        <label class="form-label" for="return_date">{{ __('recruitment.fields.returned_at') }}</label>
        <input type="date" name="date" id="return_date" min="{{ $order->delivered_at?->toDateString() }}" max="{{ $today }}" value="{{ $today }}" required class="form-control">
    </div>
    <div class="col-md-3">
        <label class="form-label" for="return_reason_id">{{ __('recruitment.fields.return_reason_id') }}</label>
        <select name="return_reason_id" id="return_reason_id" required class="form-select @error('return_reason_id') is-invalid @enderror">
            <option value="">{{ __('common.select') }}</option>
            @foreach ($returnReasons as $reason)
                <option value="{{ $reason->id }}" @selected((string) old('return_reason_id') === (string) $reason->id)>{{ $reason->trans('name') }}</option>
            @endforeach
        </select>
        @error('return_reason_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label" for="outcome">{{ __('recruitment.fields.outcome') }}</label>
        <select name="outcome" id="outcome" required class="form-select">
            @foreach (['returned', 'absconded'] as $outcome)
                <option value="{{ $outcome }}" @selected(old('outcome') === $outcome)>{{ __('recruitment.outcomes.'.$outcome) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label" for="return_note">{{ __('recruitment.fields.return_note') }}</label>
        <input type="text" name="return_note" id="return_note" maxlength="1000" value="{{ old('return_note') }}" class="form-control">
    </div>
    <div class="col-12 d-flex justify-content-end">
        <button type="submit" class="btn btn-outline-danger">{{ __('recruitment.actions.return') }}</button>
    </div>
</form>
