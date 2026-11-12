@extends('layouts.app')

@section('title', __('transport.drivers_title'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('transport.drivers_title')])

    <x-alerts :flash="false" />

    @can('drivers.create')
        <div class="card">
            <div class="card-body">
                <a class="d-flex justify-content-between align-items-center text-reset" data-bs-toggle="collapse" href="#new-driver">
                    <h5 class="card-title mb-0"><i class="ri-user-add-line me-1"></i>{{ __('transport.add_driver') }}</h5>
                    <i class="ri-arrow-down-s-line"></i>
                </a>
                <div class="collapse @if ($errors->any()) show @endif" id="new-driver">
                    <p class="text-muted font-size-13 mt-2">{{ __('transport.hints.driver') }}</p>
                    <form method="POST" action="{{ route('admin.drivers.store') }}" class="row g-2" novalidate>
                        @csrf
                        @foreach ([
                            'name_ar' => ['type' => 'text', 'required' => true],
                            'name_en' => ['type' => 'text', 'dir' => 'ltr'],
                            'phone' => ['type' => 'text', 'required' => true, 'dir' => 'ltr'],
                            'email' => ['type' => 'email', 'required' => true, 'dir' => 'ltr'],
                            'password' => ['type' => 'password', 'required' => true, 'dir' => 'ltr'],
                            'license_number' => ['type' => 'text', 'dir' => 'ltr'],
                            'license_expiry' => ['type' => 'date'],
                            'vehicle_model' => ['type' => 'text'],
                            'vehicle_plate' => ['type' => 'text', 'dir' => 'ltr'],
                            'seats' => ['type' => 'number', 'dir' => 'ltr'],
                        ] as $field => $config)
                            <div class="col-md-3">
                                <label class="form-label" for="driver_{{ $field }}">{{ __('transport.driver_fields.'.$field) }} @if ($config['required'] ?? false)<span class="text-danger">*</span>@endif</label>
                                <input type="{{ $config['type'] }}" name="{{ $field }}" id="driver_{{ $field }}" @if (isset($config['dir'])) dir="{{ $config['dir'] }}" @endif
                                    value="{{ $config['type'] === 'password' ? '' : old($field) }}" @required($config['required'] ?? false) class="form-control @error($field) is-invalid @enderror">
                                @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        @endforeach
                        <div class="col-md-3">
                            <label class="form-label" for="driver_branch">{{ __('transport.driver_fields.branch_id') }} <span class="text-danger">*</span></label>
                            <select name="branch_id" id="driver_branch" required class="form-select">
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->trans('name') }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-grid align-items-end">
                            <button type="submit" class="btn btn-primary mt-md-4"><i class="ri-save-line me-1"></i>{{ __('transport.save_driver') }}</button>
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
                            <th>{{ __('transport.fields.driver_id') }}</th>
                            <th>{{ __('transport.driver_fields.branch_id') }}</th>
                            <th>{{ __('transport.driver_fields.vehicle_model') }}</th>
                            <th>{{ __('transport.driver_fields.license_expiry') }}</th>
                            <th>{{ __('transport.trips_today') }}</th>
                            <th>{{ __('transport.fields.status') }}</th>
                            @can('drivers.edit')<th></th>@endcan
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($profiles as $profile)
                            <tr>
                                <td class="fw-semibold">{{ $profile->user?->displayName() }}<div class="text-muted font-size-12" dir="ltr">{{ $profile->user?->phone }} · {{ $profile->user?->email }}</div></td>
                                <td>{{ $profile->branch?->trans('name') }}</td>
                                <td>{{ $profile->vehicle_model ?? '—' }} <span dir="ltr" class="text-muted">{{ $profile->vehicle_plate }}</span>{{ $profile->seats ? ' · '.__('transport.seats', ['count' => $profile->seats]) : '' }}</td>
                                <td dir="ltr" class="text-start {{ $profile->license_expiry?->isPast() ? 'text-danger' : '' }}">{{ $profile->license_expiry?->format('Y-m-d') ?? '—' }}</td>
                                <td><a href="{{ route('admin.trips.index', ['driver_id' => $profile->user_id]) }}">{{ $today[$profile->user_id] ?? 0 }}</a></td>
                                <td><span class="badge {{ $profile->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $profile->is_active ? __('common.active') : __('common.inactive') }}</span></td>
                                @can('drivers.edit')
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('admin.drivers.update', $profile) }}" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            @foreach (['branch_id', 'license_number', 'vehicle_model', 'vehicle_plate', 'seats'] as $field)
                                                <input type="hidden" name="{{ $field }}" value="{{ $profile->{$field} }}">
                                            @endforeach
                                            <input type="hidden" name="license_expiry" value="{{ $profile->license_expiry?->toDateString() }}">
                                            <input type="hidden" name="is_active" value="{{ $profile->is_active ? 0 : 1 }}">
                                            <button type="submit" class="btn btn-sm {{ $profile->is_active ? 'btn-outline-secondary' : 'btn-outline-success' }}">{{ $profile->is_active ? __('transport.actions.deactivate') : __('transport.actions.activate') }}</button>
                                        </form>
                                    </td>
                                @endcan
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5">{{ __('transport.no_drivers') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
