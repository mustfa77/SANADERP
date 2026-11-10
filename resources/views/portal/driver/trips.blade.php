@extends('layouts.portal')

@section('title', __('portal.my_trips'))
@section('portal_title', __('portal.driver_title'))
@section('portal_nav')
    <li class="nav-item">
        <a class="nav-link active" href="{{ route('driver.trips.index') }}"><i class="ri-bus-line me-2"></i> {{ __('portal.my_trips') }}</a>
    </li>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-4">{{ __('portal.my_trips') }}</h4>
            <div class="table-responsive">
                <table class="table table-centered table-nowrap mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('portal.fields.scheduled_at') }}</th>
                            <th>{{ __('portal.fields.branch') }}</th>
                            <th>{{ __('portal.fields.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($trips as $trip)
                            <tr>
                                <td><span dir="ltr">{{ $trip->scheduled_at->format('Y-m-d H:i') }}</span></td>
                                <td>{{ $trip->branch?->trans('name') }}</td>
                                <td>{{ $trip->status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">{{ __('common.no_results') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $trips->links() }}</div>
        </div>
    </div>
@endsection
