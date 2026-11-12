@extends('layouts.app')

@section('title', __('rental.title'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('rental.title')])

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h4 class="card-title mb-0">{{ __('rental.title') }} <span class="text-muted font-size-14">({{ $contracts->total() }})</span></h4>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.rental.index', ['ending' => 1]) }}" class="btn {{ ($filters['ending'] ?? null) ? 'btn-warning' : 'btn-outline-warning' }}">
                        <i class="ri-alarm-warning-line me-1"></i>{{ __('rental.ending_soon') }} <span class="badge bg-danger ms-1">{{ $endingCount }}</span>
                    </a>
                    @can('create', App\Models\RentalContract::class)
                        <a href="{{ route('admin.rental.create') }}" class="btn btn-primary"><i class="ri-add-line me-1"></i>{{ __('rental.create') }}</a>
                    @endcan
                </div>
            </div>
            @if ($filters['ending'] ?? null)
                <div class="alert alert-warning py-2">{{ __('rental.hints.ending_soon', ['days' => $alertDays]) }}</div>
            @endif

            <form method="GET" action="{{ route('admin.rental.index') }}" class="row g-2 mb-4">
                <div class="col-lg-4 col-md-6">
                    <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="{{ __('rental.search_placeholder') }}">
                </div>
                <div class="col-lg-3 col-md-6">
                    <select name="status" class="form-select">
                        <option value="">{{ __('rental.fields.status') }}: {{ __('common.all') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($branches->count() > 1)
                    <div class="col-lg-3 col-md-6">
                        <select name="branch_id" class="form-select">
                            <option value="">{{ __('rental.fields.branch_id') }}: {{ __('common.all') }}</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) ($filters['branch_id'] ?? '') === (string) $branch->id)>{{ $branch->trans('name') }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-lg-2 col-md-6 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary flex-fill"><i class="ri-filter-2-line"></i></button>
                    <a href="{{ route('admin.rental.index') }}" class="btn btn-light flex-fill"><i class="ri-close-line"></i></a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-centered table-nowrap table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('rental.fields.number') }}</th>
                            <th>{{ __('rental.fields.client_id') }}</th>
                            <th>{{ __('rental.fields.worker_id') }}</th>
                            <th>{{ __('rental.fields.period') }}</th>
                            <th>{{ __('rental.fields.total') }}</th>
                            <th>{{ __('payments.title') }}</th>
                            <th>{{ __('rental.fields.status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($contracts as $contract)
                            @php
                                $left = $contract->daysLeft();
                                $payment = $contract->paymentStatus();
                            @endphp
                            <tr>
                                <td><a href="{{ route('admin.rental.show', $contract) }}" class="fw-semibold"><code dir="ltr">{{ $contract->number }}</code></a></td>
                                <td>{{ $contract->client?->trans('name') }}<div class="text-muted font-size-12" dir="ltr">{{ $contract->client?->phone }}</div></td>
                                <td>{{ $contract->worker?->trans('name') }}</td>
                                <td dir="ltr" class="text-start">
                                    {{ $contract->starts_at->format('Y-m-d') }} → {{ $contract->ends_at->format('Y-m-d') }}
                                    @if ($contract->status === \App\Enums\RentalContractStatus::Active)
                                        <div class="font-size-12 {{ $left < 0 ? 'text-danger' : ($left <= $alertDays ? 'text-warning' : 'text-muted') }}" dir="auto">
                                            {{ $left < 0 ? __('rental.overdue_days', ['count' => abs($left)]) : __('rental.days_left', ['count' => $left]) }}
                                        </div>
                                    @endif
                                </td>
                                <td>{{ number_format((float) $contract->total, 2) }}</td>
                                <td><span class="badge bg-{{ $payment->color() }}-subtle text-{{ $payment->color() }}">{{ $payment->label() }}</span></td>
                                <td><span class="badge font-size-12 bg-{{ $contract->status->color() }}-subtle text-{{ $contract->status->color() }}">{{ $contract->status->label() }}</span></td>
                                <td class="text-end"><a href="{{ route('admin.rental.show', $contract) }}" class="btn btn-sm btn-outline-secondary"><i class="ri-eye-line"></i></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">{{ __('common.no_results') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $contracts->links() }}</div>
        </div>
    </div>
@endsection
