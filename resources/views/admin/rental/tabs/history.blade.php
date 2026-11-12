{{-- Worker / client profile: monthly contracts and hourly bookings ($worker or $client). --}}
@php
    $owner = $worker ?? $client;
    $isClient = isset($client);
    $contracts = $owner->rentalContracts()->with([$isClient ? 'worker' : 'client'])->withPaymentTotals()->get();
    $bookings = auth()->user()->can('hourly_bookings.view')
        ? $owner->hourlyBookings()->with([$isClient ? 'worker' : 'client', 'shift'])->limit(50)->get()
        : collect();
@endphp

<div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
    @can('create', App\Models\RentalContract::class)
        <a href="{{ route('admin.rental.create', [$isClient ? 'client' : 'worker' => $owner->id]) }}" class="btn btn-sm btn-primary"><i class="ri-add-line me-1"></i>{{ __('rental.create') }}</a>
    @endcan
    @can('create', App\Models\HourlyBooking::class)
        <a href="{{ route('admin.hourly.create', [$isClient ? 'client' : 'worker' => $owner->id]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-time-line me-1"></i>{{ __('hourly.create') }}</a>
    @endcan
</div>

<h6 class="mb-2">{{ __('rental.title') }}</h6>
<div class="table-responsive mb-4">
    <table class="table table-sm table-centered mb-0">
        <thead class="table-light">
            <tr>
                <th>{{ __('rental.fields.number') }}</th>
                <th>{{ $isClient ? __('rental.fields.worker_id') : __('rental.fields.client_id') }}</th>
                <th>{{ __('rental.fields.period') }}</th>
                <th>{{ __('rental.fields.total') }}</th>
                <th>{{ __('payments.title') }}</th>
                <th>{{ __('rental.fields.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($contracts as $contract)
                <tr>
                    <td><a href="{{ route('admin.rental.show', $contract) }}" dir="ltr">{{ $contract->number }}</a></td>
                    <td>{{ ($isClient ? $contract->worker : $contract->client)?->trans('name') }}</td>
                    <td dir="ltr" class="text-start">{{ $contract->starts_at->format('Y-m-d') }} → {{ $contract->ends_at->format('Y-m-d') }}</td>
                    <td>{{ number_format((float) $contract->total, 2) }}</td>
                    <td><span class="badge bg-{{ $contract->paymentStatus()->color() }}-subtle text-{{ $contract->paymentStatus()->color() }}">{{ $contract->paymentStatus()->label() }}</span></td>
                    <td><span class="badge bg-{{ $contract->status->color() }}-subtle text-{{ $contract->status->color() }}">{{ $contract->status->label() }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-3">{{ __('rental.none') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@can('hourly_bookings.view')
    <h6 class="mb-2">{{ __('hourly.title') }}</h6>
    <div class="table-responsive">
        <table class="table table-sm table-centered mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('hourly.fields.number') }}</th>
                    <th>{{ $isClient ? __('hourly.fields.worker_id') : __('hourly.fields.client_id') }}</th>
                    <th>{{ __('hourly.fields.date') }}</th>
                    <th>{{ __('hourly.fields.shift_id') }}</th>
                    <th>{{ __('hourly.fields.total') }}</th>
                    <th>{{ __('hourly.fields.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($bookings as $booking)
                    <tr>
                        <td><a href="{{ route('admin.hourly.show', $booking) }}" dir="ltr">{{ $booking->number }}</a></td>
                        <td>{{ ($isClient ? $booking->worker : $booking->client)?->trans('name') }}</td>
                        <td dir="ltr" class="text-start">{{ $booking->date->format('Y-m-d') }}</td>
                        <td>{{ $booking->shift?->trans('name') }}</td>
                        <td>{{ number_format((float) $booking->total, 2) }}</td>
                        <td><span class="badge bg-{{ $booking->status->color() }}-subtle text-{{ $booking->status->color() }}">{{ $booking->status->label() }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">{{ __('hourly.none') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endcan
