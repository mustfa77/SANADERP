{{-- Refund requests with the finance decision buttons. Expects $refunds and $showPayable. --}}
<div class="table-responsive mb-3">
    <table class="table table-sm table-centered mb-0">
        <thead class="table-light">
            <tr>
                @if ($showPayable)
                    <th>{{ __('refunds.fields.payable') }}</th>
                    <th>{{ __('rental.fields.client_id') }}</th>
                @endif
                <th>{{ __('refunds.fields.amount') }}</th>
                <th>{{ __('refunds.fields.reason') }}</th>
                <th>{{ __('refunds.fields.requested') }}</th>
                <th>{{ __('refunds.fields.status') }}</th>
                <th class="text-end"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($refunds as $refund)
                <tr>
                    @if ($showPayable)
                        <td>
                            @if ($refund->payable instanceof App\Models\RentalContract)
                                <a href="{{ route('admin.rental.show', $refund->payable) }}" dir="ltr">{{ $refund->payable->number }}</a>
                            @elseif ($refund->payable instanceof App\Models\HourlyBooking)
                                <a href="{{ route('admin.hourly.show', $refund->payable) }}" dir="ltr">{{ $refund->payable->number }}</a>
                            @endif
                        </td>
                        <td>{{ $refund->payable?->client?->trans('name') }}</td>
                    @endif
                    <td class="fw-semibold">{{ number_format((float) $refund->amount, 2) }}</td>
                    <td class="text-wrap font-size-13">{{ $refund->reason }}</td>
                    <td class="font-size-12">
                        {{ $refund->requester?->displayName() }}
                        <div class="text-muted" dir="ltr">{{ $refund->created_at->format('Y-m-d H:i') }}</div>
                    </td>
                    <td>
                        <span class="badge bg-{{ $refund->status->color() }}-subtle text-{{ $refund->status->color() }}">{{ $refund->status->label() }}</span>
                        @if ($refund->decider)
                            <div class="text-muted font-size-11">{{ $refund->decider->displayName() }}{{ $refund->decision_note ? ' — '.$refund->decision_note : '' }}</div>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('decide', $refund)
                            <div class="d-inline-flex gap-1">
                                <form method="POST" action="{{ route('admin.refunds.approve', $refund) }}" data-confirm="{{ __('refunds.actions.approve') }}?" data-confirm-text="{{ __('refunds.confirm_approve', ['amount' => number_format((float) $refund->amount, 2)]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">{{ __('refunds.actions.approve') }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.refunds.reject', $refund) }}" data-confirm="{{ __('refunds.actions.reject') }}?">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('refunds.actions.reject') }}</button>
                                </form>
                            </div>
                        @endcan
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
