{{-- Delivery to the client (order "arrived"). Fees are calculated from Settings > Recruitment and may be changed or waived. --}}
@php
    $quote = $quotes['office'];
    $method = old('delivery_method', 'office');
    $feeConfig = [
        'arrivedAt' => $order->arrived_at?->toDateString(),
        'hasCamp' => $order->arrival_camp_id !== null,
        'freeDays' => $feeSettings['camp_free_days'],
        'dailyFee' => $feeSettings['camp_daily_fee'],
        'homeFee' => $feeSettings['home_delivery_fee'],
        'handoverUrl' => route('admin.recruitment.handover', $order),
    ];
@endphp
<div class="alert alert-info py-2 font-size-13">
    <i class="ri-home-4-line me-1"></i>
    @if ($order->arrivalCamp)
        {{ __('recruitment.hints.camp_stay', ['camp' => $order->arrivalCamp->trans('name'), 'days' => $quote['camp_days'], 'free' => $feeSettings['camp_free_days'], 'fee' => $feeSettings['camp_daily_fee']]) }}
    @else
        {{ __('recruitment.hints.no_camp_stay') }}
    @endif
</div>

<form method="POST" action="{{ route('admin.recruitment.deliver', $order) }}" id="delivery-form" class="row g-3" novalidate
    data-confirm="{{ __('recruitment.actions.deliver') }}?" data-confirm-text="{{ __('recruitment.confirm_deliver') }}">
    @csrf
    <div class="col-md-4">
        <label class="form-label" for="delivered_at">{{ __('recruitment.fields.delivered_at') }}</label>
        <input type="date" name="delivered_at" id="delivered_at" min="{{ $order->arrived_at?->toDateString() }}" max="{{ $today }}" value="{{ old('delivered_at', $today) }}" required class="form-control @error('delivered_at') is-invalid @enderror">
        @error('delivered_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-8">
        <label class="form-label d-block">{{ __('recruitment.fields.delivery_method') }}</label>
        @foreach (\App\Enums\DeliveryMethod::cases() as $option)
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="delivery_method" id="method_{{ $option->value }}" value="{{ $option->value }}" @checked($method === $option->value)>
                <label class="form-check-label" for="method_{{ $option->value }}">
                    {{ $option->label() }}
                    @if ($option === \App\Enums\DeliveryMethod::Home)
                        <span class="text-muted font-size-12">({{ number_format($feeSettings['home_delivery_fee']) }} {{ __('dashboard.sar') }})</span>
                    @endif
                </label>
            </div>
        @endforeach
        @error('delivery_method') <div class="text-danger font-size-12">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="receiver_name">{{ __('recruitment.fields.receiver_name') }}</label>
        <input type="text" name="receiver_name" id="receiver_name" maxlength="255" value="{{ old('receiver_name', $order->client?->trans('name')) }}" required class="form-control @error('receiver_name') is-invalid @enderror">
        @error('receiver_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="receiver_phone">{{ __('recruitment.fields.receiver_phone') }}</label>
        <input type="text" name="receiver_phone" id="receiver_phone" dir="ltr" maxlength="20" value="{{ old('receiver_phone', $order->client?->phone) }}" class="form-control @error('receiver_phone') is-invalid @enderror">
        @error('receiver_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="camp_fee">{{ __('recruitment.fields.camp_fee') }} <span class="text-muted font-size-12" id="camp-days-label"></span></label>
        <div class="input-group">
            <input type="number" name="camp_fee" id="camp_fee" min="0" step="0.01" dir="ltr" value="{{ old('camp_fee', $quote['camp_fee']) }}" class="form-control @error('camp_fee') is-invalid @enderror">
            <span class="input-group-text">{{ __('dashboard.sar') }}</span>
            @error('camp_fee') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="delivery_fee">{{ __('recruitment.fields.delivery_fee') }}</label>
        <div class="input-group">
            <input type="number" name="delivery_fee" id="delivery_fee" min="0" step="0.01" dir="ltr" value="{{ old('delivery_fee', $quotes[$method]['delivery_fee'] ?? 0) }}" class="form-control @error('delivery_fee') is-invalid @enderror">
            <span class="input-group-text">{{ __('dashboard.sar') }}</span>
            @error('delivery_fee') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('recruitment.fields.fees_total') }}</label>
        <div class="form-control bg-light fw-semibold" id="fees-total" dir="ltr">0.00</div>
        <div class="form-text">{{ __('recruitment.hints.fees_editable') }}</div>
    </div>

    <div class="col-12">
        <label class="form-label" for="delivery_notes">{{ __('recruitment.fields.delivery_notes') }}</label>
        <textarea name="delivery_notes" id="delivery_notes" rows="2" maxlength="1000" class="form-control @error('delivery_notes') is-invalid @enderror">{{ old('delivery_notes') }}</textarea>
        @error('delivery_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 d-flex flex-wrap gap-2 justify-content-between">
        <a href="{{ route('admin.recruitment.handover', [$order, 'method' => $method]) }}" target="_blank" id="handover-draft" class="btn btn-outline-secondary">
            <i class="ri-printer-line me-1"></i>{{ __('recruitment.actions.print_handover_draft') }}
        </a>
        <button type="submit" class="btn btn-success">
            <i class="ri-hand-heart-line me-1"></i>{{ __('recruitment.actions.deliver') }}
        </button>
    </div>
</form>

@push('scripts')
    <script>
        (function () {
            var config = {{ Js::from($feeConfig) }};
            var form = document.getElementById('delivery-form');
            var date = document.getElementById('delivered_at');
            var campFee = document.getElementById('camp_fee');
            var deliveryFee = document.getElementById('delivery_fee');
            var total = document.getElementById('fees-total');
            var daysLabel = document.getElementById('camp-days-label');
            var draft = document.getElementById('handover-draft');

            function method() {
                var checked = form.querySelector('input[name="delivery_method"]:checked');
                return checked ? checked.value : 'office';
            }

            function campDays() {
                if (! config.hasCamp || ! config.arrivedAt || ! date.value) {
                    return 0;
                }
                var days = Math.round((Date.parse(date.value) - Date.parse(config.arrivedAt)) / 86400000);
                return Math.max(0, days);
            }

            function sum() {
                total.textContent = ((parseFloat(campFee.value) || 0) + (parseFloat(deliveryFee.value) || 0)).toFixed(2);
            }

            // Recalculate from the settings; the user may still edit the amounts.
            function recalculate() {
                var days = campDays();
                daysLabel.textContent = config.hasCamp ? '(' + days + ')' : '';
                campFee.value = (Math.max(0, days - config.freeDays) * config.dailyFee).toFixed(2);
                deliveryFee.value = (method() === 'home' ? config.homeFee : 0).toFixed(2);
                draft.href = config.handoverUrl + '?method=' + method();
                sum();
            }

            date.addEventListener('change', recalculate);
            form.querySelectorAll('input[name="delivery_method"]').forEach(function (radio) {
                radio.addEventListener('change', recalculate);
            });
            [campFee, deliveryFee].forEach(function (input) {
                input.addEventListener('input', sum);
            });

            daysLabel.textContent = config.hasCamp ? '(' + campDays() + ')' : '';
            sum();
        })();
    </script>
@endpush
