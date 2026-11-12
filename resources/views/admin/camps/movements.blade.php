@extends('layouts.app')

@section('title', __('camps.movements_title'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('camps.movements_title'), 'parent' => __('camps.title')])

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.camps.movements') }}" class="row g-2 align-items-end">
                <div class="col-lg-3 col-md-6">
                    <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="{{ __('camps.search_worker') }}">
                </div>
                <div class="col-lg-2 col-md-6">
                    <select name="camp_id" class="form-select">
                        <option value="">{{ __('camps.fields.camp') }}: {{ __('common.all') }}</option>
                        @foreach ($camps as $camp)
                            <option value="{{ $camp->id }}" @selected((string) ($filters['camp_id'] ?? '') === (string) $camp->id)>{{ $camp->trans('name') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <select name="type" class="form-select">
                        <option value="">{{ __('camps.fields.type') }}: {{ __('common.all') }}</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}" @selected(($filters['type'] ?? '') === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-4"><input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control" title="{{ __('hourly.from') }}"></div>
                <div class="col-lg-2 col-md-4"><input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control" title="{{ __('hourly.to') }}"></div>
                <div class="col-lg-1 col-md-12 d-grid"><button type="submit" class="btn btn-outline-primary"><i class="ri-filter-2-line"></i></button></div>
            </form>
        </div>
    </div>

    @include('admin.camps._movements-table', ['movements' => $movements, 'showWorker' => true])
@endsection
