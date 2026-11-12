{{-- Active contract: worker returned (end / early), replace the worker, or cancel. Times are date + hour.
     The return calculation mirrors RentalContractService::returnQuote (return before the cut-off hour: the day is not charged). --}}
@php
    $nowLocal = now()->format('Y-m-d\TH:i');
    $minLocal = $contract->starts_at->format('Y-m-d\T00:00');
    $action = old('return_action', 'close');
@endphp

<ul class="nav nav-pills nav-justified bg-light rounded p-1 mt-4 mb-3" role="tablist">
    @foreach (['close' => 'ri-logout-box-r-line', 'replace' => 'ri-user-shared-line', 'cancel' => 'ri-close-circle-line'] as $key => $icon)
        <li class="nav-item">
            <a class="nav-link @if ($action === $key) active @endif" data-bs-toggle="tab" href="#return-{{ $key }}" role="tab"><i class="{{ $icon }} me-1"></i>{{ __('rental.return_actions.'.$key) }}</a>
        </li>
    @endforeach
</ul>

<div class="tab-content">
    {{-- Worker returned --}}
    <div class="tab-pane @if ($action === 'close') active @endif" id="return-close" role="tabpanel">
        <p class="text-muted font-size-13">{{ __('rental.hints.close', ['hour' => $returnRule['cutoff'].':00']) }}</p>
        <form method="POST" action="{{ route('admin.rental.close', $contract) }}" class="row g-2" id="close-form"
            data-confirm="{{ __('rental.actions.close') }}?" data-confirm-text="{{ __('rental.confirm_close') }}">
            @csrf
            <input type="hidden" name="return_action" value="close">
            <div class="col-md-4">
                <label class="form-label" for="close_returned_at">{{ __('rental.fields.returned_at') }}</label>
                <input type="datetime-local" name="returned_at" id="close_returned_at" min="{{ $minLocal }}" max="{{ $nowLocal }}" value="{{ old('returned_at', $nowLocal) }}" required class="form-control return-time @error('returned_at') is-invalid @enderror">
                @error('returned_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            @include('admin.rental._return-common', ['prefix' => 'close', 'reasonRequired' => false])

            <div class="col-12" id="early-box">
                <div class="alert alert-warning py-2 mb-2 font-size-13" id="early-text"></div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label" for="charge">{{ __('rental.fields.charge') }}</label>
                        <input type="number" name="charge" id="charge" step="0.01" min="0" dir="ltr" value="{{ old('charge') }}" class="form-control">
                        <div class="form-text">{{ __('rental.hints.charge') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="close_refund">{{ __('rental.fields.refund') }}</label>
                        <input type="number" name="refund" id="close_refund" step="0.01" min="0" dir="ltr" value="{{ old('refund') }}" class="form-control">
                        <div class="form-text">{{ __('rental.hints.refund') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-danger"><i class="ri-logout-box-r-line me-1"></i>{{ __('rental.actions.close') }}</button>
            </div>
        </form>
    </div>

    {{-- Replace the worker --}}
    <div class="tab-pane @if ($action === 'replace') active @endif" id="return-replace" role="tabpanel">
        <p class="text-muted font-size-13">{{ __('rental.hints.replace') }}</p>
        @if ($replacements->isEmpty())
            <div class="alert alert-warning py-2">{{ __('rental.no_replacements') }}</div>
        @else
            <form method="POST" action="{{ route('admin.rental.replace', $contract) }}" class="row g-2"
                data-confirm="{{ __('rental.actions.replace') }}?" data-confirm-text="{{ __('rental.confirm_replace') }}">
                @csrf
                <input type="hidden" name="return_action" value="replace">
                <div class="col-md-4">
                    <label class="form-label" for="replace_returned_at">{{ __('rental.fields.old_returned_at') }}</label>
                    <input type="datetime-local" name="returned_at" id="replace_returned_at" min="{{ $minLocal }}" max="{{ $nowLocal }}" value="{{ old('returned_at', $nowLocal) }}" required class="form-control">
                </div>
                @include('admin.rental._return-common', ['prefix' => 'replace', 'reasonRequired' => true])
                <div class="col-md-8">
                    <label class="form-label" for="new_worker_id">{{ __('rental.fields.new_worker_id') }}</label>
                    <select name="new_worker_id" id="new_worker_id" required class="form-select select2 @error('new_worker_id') is-invalid @enderror">
                        <option value="">{{ __('common.select') }}</option>
                        @foreach ($replacements as $replacement)
                            <option value="{{ $replacement->id }}" @selected((string) old('new_worker_id') === (string) $replacement->id)>
                                {{ $replacement->number() }} — {{ $replacement->trans('name') }} — {{ $replacement->country?->trans('nationality') }} · {{ $replacement->profession?->trans('name') }}
                            </option>
                        @endforeach
                    </select>
                    @error('new_worker_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="departed_at">{{ __('rental.fields.new_departed_at') }}</label>
                    <input type="datetime-local" name="departed_at" id="departed_at" value="{{ old('departed_at', $nowLocal) }}" required class="form-control @error('departed_at') is-invalid @enderror">
                    @error('departed_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-warning"><i class="ri-user-shared-line me-1"></i>{{ __('rental.actions.replace') }}</button>
                </div>
            </form>
        @endif
    </div>

    {{-- Cancel --}}
    <div class="tab-pane @if ($action === 'cancel') active @endif" id="return-cancel" role="tabpanel">
        <p class="text-muted font-size-13">{{ __('rental.hints.cancel') }}</p>
        <form method="POST" action="{{ route('admin.rental.cancel', $contract) }}" class="row g-2"
            data-confirm="{{ __('rental.actions.cancel') }}?" data-confirm-text="{{ __('rental.confirm_cancel') }}">
            @csrf
            <input type="hidden" name="return_action" value="cancel">
            <div class="col-md-4">
                <label class="form-label" for="cancel_returned_at">{{ __('rental.fields.returned_at') }}</label>
                <input type="datetime-local" name="returned_at" id="cancel_returned_at" min="{{ $minLocal }}" max="{{ $nowLocal }}" value="{{ old('returned_at', $nowLocal) }}" required class="form-control">
            </div>
            @include('admin.rental._return-common', ['prefix' => 'cancel', 'reasonRequired' => true])
            <div class="col-md-6">
                <label class="form-label" for="cancel_refund">{{ __('rental.fields.refund') }}</label>
                <input type="number" name="refund" id="cancel_refund" step="0.01" min="0" max="{{ $returnRule['paid'] }}" dir="ltr" value="{{ old('refund', number_format(max(0, $returnRule['paid']), 2, '.', '')) }}" class="form-control">
                <div class="form-text">{{ __('rental.hints.cancel_refund', ['paid' => number_format($returnRule['paid'], 2)]) }}</div>
            </div>
            <div class="col-md-6 d-flex align-items-end justify-content-end">
                <button type="submit" class="btn btn-outline-danger"><i class="ri-close-circle-line me-1"></i>{{ __('rental.actions.cancel') }}</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        (function () {
            var rule = {{ Js::from($returnRule) }};
            var text = {{ Js::from(['early' => __('rental.return_quote.early'), 'onTime' => __('rental.return_quote.on_time')]) }};
            var time = document.getElementById('close_returned_at');
            var box = document.getElementById('early-box');
            var label = document.getElementById('early-text');
            var charge = document.getElementById('charge');
            var refund = document.getElementById('close_refund');

            function day(value) {
                return new Date(value + 'T00:00:00');
            }

            // Used days: from the start to the return day; the return day is free before the cut-off hour.
            function quote() {
                if (! time.value) {
                    return;
                }
                var back = new Date(time.value);
                var backDay = day(time.value.substring(0, 10));
                var early = backDay < day(rule.ends);
                box.classList.toggle('d-none', ! early);
                if (! early) {
                    label.textContent = text.onTime;
                    return;
                }
                var last = new Date(backDay);
                if (back.getHours() < rule.cutoff) {
                    last.setDate(last.getDate() - 1);
                }
                var used = last < day(rule.starts) ? 0 : Math.round((last - day(rule.starts)) / 86400000) + 1;
                used = Math.min(rule.days, used);
                var due = Math.round(rule.total * used / rule.days * 100) / 100;
                var back_ = Math.max(0, Math.round((rule.paid - due) * 100) / 100);
                charge.value = due.toFixed(2);
                refund.value = back_.toFixed(2);
                label.textContent = text.early.replace(':used', used).replace(':days', rule.days).replace(':charge', due.toFixed(2)).replace(':paid', rule.paid.toFixed(2)).replace(':refund', back_.toFixed(2));
            }

            time.addEventListener('change', quote);
            charge.addEventListener('input', function () {
                refund.value = Math.max(0, rule.paid - (parseFloat(charge.value) || 0)).toFixed(2);
            });
            quote();
        })();
    </script>
@endpush
