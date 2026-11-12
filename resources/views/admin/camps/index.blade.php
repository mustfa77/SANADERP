@extends('layouts.app')

@section('title', __('camps.title'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('camps.title')])

    <div class="row">
        @foreach ([
            'camps' => [$camps->count(), 'ri-hotel-bed-line', 'primary'],
            'housed' => [$camps->sum('workers_count'), 'ri-team-line', 'success'],
            'movements_today' => [$movementsToday, 'ri-arrow-left-right-line', 'info'],
            'unhoused' => [$unhoused, 'ri-error-warning-line', 'warning'],
        ] as $key => [$value, $icon, $color])
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body d-flex align-items-center">
                        <div class="avatar-sm me-3"><span class="avatar-title rounded bg-{{ $color }}-subtle text-{{ $color }} font-size-20"><i class="{{ $icon }}"></i></span></div>
                        <div>
                            <h4 class="mb-0">{{ $value }}</h4>
                            <div class="text-muted font-size-13">{{ __('camps.summary.'.$key) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($unhoused > 0)
        <div class="alert alert-warning py-2"><i class="ri-information-line me-1"></i>{{ __('camps.hints.unhoused', ['count' => $unhoused]) }}</div>
    @endif

    <div class="row">
        @forelse ($camps as $camp)
            @php
                $taken = $attendance->get($camp->id, collect());
                $percent = $camp->capacity ? min(100, (int) round($camp->workers_count * 100 / $camp->capacity)) : null;
                $counts = $taken->countBy(fn ($row) => $row->status->value);
            @endphp
            <div class="col-xl-4 col-md-6">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h5 class="font-size-16 mb-1"><a href="{{ route('admin.camps.show', $camp) }}" class="text-reset">{{ $camp->trans('name') }}</a></h5>
                                <div class="text-muted font-size-12">{{ $camp->branch?->trans('name') }}{{ $camp->type ? ' · '.$camp->type->label() : '' }}</div>
                            </div>
                            <span class="badge bg-light text-dark font-size-13" dir="ltr">{{ $camp->workers_count }} / {{ $camp->capacity ?: '∞' }}</span>
                        </div>

                        @if ($percent !== null)
                            <div class="progress mb-3" style="height: 6px;">
                                <div class="progress-bar {{ $percent >= 90 ? 'bg-danger' : ($percent >= 70 ? 'bg-warning' : 'bg-success') }}" style="width: {{ $percent }}%"></div>
                            </div>
                        @endif

                        <div class="font-size-13 mb-3">
                            @if ($taken->isEmpty())
                                <span class="text-warning"><i class="ri-alarm-warning-line me-1"></i>{{ __('camps.attendance_not_taken') }}</span>
                            @else
                                @foreach (\App\Enums\AttendanceStatus::cases() as $status)
                                    @if ($counts->get($status->value))
                                        <span class="badge bg-{{ $status->color() }}-subtle text-{{ $status->color() }} me-1">{{ $status->label() }}: {{ $counts->get($status->value) }}</span>
                                    @endif
                                @endforeach
                            @endif
                        </div>

                        <div class="mt-auto d-flex gap-2">
                            <a href="{{ route('admin.camps.show', $camp) }}" class="btn btn-sm btn-primary flex-fill"><i class="ri-team-line me-1"></i>{{ __('camps.tabs.workers') }}</a>
                            <a href="{{ route('admin.camps.show', [$camp, 'tab' => 'attendance']) }}" class="btn btn-sm btn-outline-primary flex-fill"><i class="ri-checkbox-multiple-line me-1"></i>{{ __('camps.tabs.attendance') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="card"><div class="card-body text-center text-muted py-5">{{ __('camps.none') }}</div></div></div>
        @endforelse
    </div>
@endsection
