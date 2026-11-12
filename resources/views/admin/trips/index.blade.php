@extends('layouts.app')
@use('App\Enums\TripStatus')

@section('title', __('transport.title'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('transport.title')])

    <x-alerts :flash="false" />

    @php
        $canEdit = auth()->user()->can('trips.edit');
    @endphp

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.trips.index') }}" class="row g-2 align-items-end">
                <div class="col-lg-3 col-md-4">
                    <label class="form-label font-size-12 mb-1" for="date">{{ __('transport.fields.date') }}</label>
                    <div class="input-group">
                        <a href="{{ route('admin.trips.index', [...$filters, 'date' => $date->copy()->subDay()->toDateString()]) }}" class="btn btn-light"><i class="ri-arrow-right-s-line"></i></a>
                        <input type="date" name="date" id="date" value="{{ $date->toDateString() }}" class="form-control" onchange="this.form.submit()">
                        <a href="{{ route('admin.trips.index', [...$filters, 'date' => $date->copy()->addDay()->toDateString()]) }}" class="btn btn-light"><i class="ri-arrow-left-s-line"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4">
                    <select name="driver_id" class="form-select">
                        <option value="">{{ __('transport.fields.driver_id') }}: {{ __('common.all') }}</option>
                        <option value="none" @selected(($filters['driver_id'] ?? '') === 'none')>{{ __('transport.unassigned') }}</option>
                        @foreach ($allDrivers as $driver)
                            <option value="{{ $driver->id }}" @selected((string) ($filters['driver_id'] ?? '') === (string) $driver->id)>{{ $driver->displayName() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <select name="type" class="form-select">
                        <option value="">{{ __('transport.fields.type') }}: {{ __('common.all') }}</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}" @selected(($filters['type'] ?? '') === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <select name="status" class="form-select">
                        <option value="">{{ __('transport.fields.status') }}: {{ __('common.all') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-1 col-md-4 d-grid"><button type="submit" class="btn btn-outline-primary"><i class="ri-filter-2-line"></i></button></div>
                <div class="col-lg-2 col-md-4 d-grid">
                    @can('trips.create')
                        <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#new-trip"><i class="ri-add-line me-1"></i>{{ __('transport.create') }}</button>
                    @endcan
                </div>
            </form>

            @if ($unassigned > 0)
                <div class="alert alert-warning py-2 mt-3 mb-0"><i class="ri-error-warning-line me-1"></i>{{ __('transport.hints.unassigned', ['count' => $unassigned]) }}</div>
            @endif
        </div>
    </div>

    @can('trips.create')
        <div class="collapse @if ($errors->any()) show @endif" id="new-trip">
            <div class="card border border-primary-subtle">
                <div class="card-body">
                    <h5 class="card-title mb-3">{{ __('transport.create') }}</h5>
                    <form method="POST" action="{{ route('admin.trips.store') }}" class="row g-2" novalidate>
                        @csrf
                        <div class="col-md-3">
                            <label class="form-label" for="branch_id">{{ __('transport.fields.branch_id') }}</label>
                            <select name="branch_id" id="branch_id" required class="form-select">
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->trans('name') }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="scheduled_at">{{ __('transport.fields.scheduled_at') }}</label>
                            <input type="datetime-local" name="scheduled_at" id="scheduled_at" required value="{{ old('scheduled_at', $date->copy()->setTime(now()->hour + 1, 0)->format('Y-m-d\TH:i')) }}" class="form-control @error('scheduled_at') is-invalid @enderror">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="from_place">{{ __('transport.fields.from_place') }}</label>
                            <input type="text" name="from_place" id="from_place" required maxlength="500" value="{{ old('from_place') }}" class="form-control @error('from_place') is-invalid @enderror">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="to_place">{{ __('transport.fields.to_place') }}</label>
                            <input type="text" name="to_place" id="to_place" required maxlength="500" value="{{ old('to_place') }}" class="form-control @error('to_place') is-invalid @enderror">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="trip_worker">{{ __('transport.fields.worker_id') }}</label>
                            <select name="worker_id" id="trip_worker" class="form-select select2">
                                <option value="">—</option>
                                @foreach ($workers as $worker)
                                    <option value="{{ $worker->id }}" @selected((string) old('worker_id') === (string) $worker->id)>{{ $worker->trans('name') }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="trip_client">{{ __('transport.fields.client_id') }}</label>
                            <select name="client_id" id="trip_client" class="form-select select2">
                                <option value="">—</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}" @selected((string) old('client_id') === (string) $client->id)>{{ $client->trans('name') }} — {{ $client->phone }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="trip_driver">{{ __('transport.fields.driver_id') }}</label>
                            <select name="driver_id" id="trip_driver" class="form-select">
                                <option value="">{{ __('transport.unassigned') }}</option>
                                @foreach ($allDrivers as $driver)
                                    <option value="{{ $driver->id }}" @selected((string) old('driver_id') === (string) $driver->id)>{{ $driver->displayName() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="trip_notes">{{ __('transport.fields.notes') }}</label>
                            <input type="text" name="notes" id="trip_notes" maxlength="1000" value="{{ old('notes') }}" class="form-control">
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>{{ __('transport.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-centered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('transport.fields.scheduled_at') }}</th>
                            <th>{{ __('transport.fields.type') }}</th>
                            <th>{{ __('transport.fields.route') }}</th>
                            <th>{{ __('transport.fields.worker_id') }}</th>
                            <th style="min-width: 220px;">{{ __('transport.fields.driver_id') }}</th>
                            <th>{{ __('transport.fields.status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($trips as $trip)
                            <tr @class(['table-warning' => $trip->status === TripStatus::Scheduled && ! $trip->driver_id])>
                                <td class="fw-semibold" dir="ltr">{{ $trip->scheduled_at->format('H:i') }}<div class="text-muted font-size-11">{{ $trip->number }}</div></td>
                                <td><i class="{{ $trip->type->icon() }} me-1"></i>{{ $trip->type->label() }}</td>
                                <td class="font-size-13">{{ $trip->from_place ?? '—' }} <i class="ri-arrow-left-line mx-1"></i> {{ $trip->to_place ?? '—' }}
                                    @if ($trip->notes)<div class="text-muted font-size-11">{{ $trip->notes }}</div>@endif
                                </td>
                                <td>{{ $trip->worker?->trans('name') ?? '—' }}
                                    @if ($trip->client)<div class="text-muted font-size-12">{{ $trip->client->trans('name') }} <span dir="ltr">{{ $trip->client->phone }}</span></div>@endif
                                </td>
                                <td>
                                    @if ($canEdit && $trip->status->isOpen())
                                        <form method="POST" action="{{ route('admin.trips.assign', $trip) }}" class="d-flex gap-1">
                                            @csrf
                                            <select name="driver_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                                <option value="">{{ __('transport.unassigned') }}</option>
                                                @foreach ($drivers[$trip->branch_id] ?? [] as $driver)
                                                    <option value="{{ $driver->id }}" @selected($trip->driver_id === $driver->id)>{{ $driver->displayName() }}</option>
                                                @endforeach
                                            </select>
                                        </form>
                                    @else
                                        {{ $trip->driver?->displayName() ?? '—' }}
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $trip->status->color() }}-subtle text-{{ $trip->status->color() }}">{{ $trip->status->label() }}</span>
                                    @if ($trip->started_at)<div class="text-muted font-size-11" dir="ltr">{{ $trip->started_at->format('H:i') }}{{ $trip->completed_at ? ' → '.$trip->completed_at->format('H:i') : '' }}</div>@endif
                                </td>
                                <td class="text-end text-nowrap">
                                    @if ($canEdit && $trip->status->isOpen())
                                        @if ($trip->status === TripStatus::Scheduled)
                                            <form method="POST" action="{{ route('admin.trips.start', $trip) }}" class="d-inline">@csrf<button type="submit" class="btn btn-sm btn-outline-primary" title="{{ __('transport.actions.start') }}" @disabled(! $trip->driver_id)><i class="ri-play-line"></i></button></form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.trips.complete', $trip) }}" class="d-inline">@csrf<button type="submit" class="btn btn-sm btn-outline-success" title="{{ __('transport.actions.complete') }}" @disabled(! $trip->driver_id)><i class="ri-check-line"></i></button></form>
                                        <form method="POST" action="{{ route('admin.trips.cancel', $trip) }}" class="d-inline" data-confirm="{{ __('transport.actions.cancel') }}?">@csrf<button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('transport.actions.cancel') }}"><i class="ri-close-line"></i></button></form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5">{{ __('transport.none') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
