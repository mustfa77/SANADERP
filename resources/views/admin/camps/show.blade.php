@extends('layouts.app')

@section('title', $camp->trans('name'))

@section('content')
    @include('layouts.partials.page-title', ['title' => $camp->trans('name'), 'parent' => __('camps.title')])

    <x-alerts :flash="false" />

    @php
        $canMove = auth()->user()->can('camp_movements.create');
    @endphp

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h4 class="card-title mb-1">{{ $camp->trans('name') }}</h4>
                    <div class="text-muted font-size-13">{{ $camp->trans('district') }} {{ $camp->type ? '· '.$camp->type->label() : '' }}</div>
                </div>
                <span class="badge bg-light text-dark font-size-14" dir="ltr">{{ $occupancy }} / {{ $camp->capacity ?: '∞' }}</span>
            </div>

            <ul class="nav nav-tabs nav-tabs-custom">
                @foreach (['workers' => 'ri-team-line', 'attendance' => 'ri-checkbox-multiple-line', 'movements' => 'ri-arrow-left-right-line'] as $key => $icon)
                    <li class="nav-item">
                        <a class="nav-link @if ($tab === $key) active @endif" href="{{ route('admin.camps.show', [$camp, 'tab' => $key]) }}"><i class="{{ $icon }} me-1"></i>{{ __('camps.tabs.'.$key) }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    @if ($tab === 'workers')
        @if ($canMove)
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="ri-login-box-line me-1"></i>{{ __('camps.check_in') }}</h5>
                    @if ($movable->isEmpty())
                        <p class="text-muted mb-0">{{ __('camps.hints.no_movable') }}</p>
                    @else
                        <form method="POST" action="{{ route('admin.camps.check-in', $camp) }}" class="row g-2 align-items-end">
                            @csrf
                            <div class="col-md-5">
                                <label class="form-label" for="worker_id">{{ __('camps.fields.worker_id') }}</label>
                                <select name="worker_id" id="worker_id" required class="form-select select2">
                                    <option value="">{{ __('common.select') }}</option>
                                    @foreach ($movable as $worker)
                                        <option value="{{ $worker->id }}">{{ $worker->number() }} — {{ $worker->trans('name') }} — {{ $worker->status->label() }}{{ $worker->camp ? ' — '.$worker->camp->trans('name') : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="check_in_note">{{ __('camps.fields.note') }}</label>
                                <input type="text" name="note" id="check_in_note" maxlength="500" required class="form-control @error('note') is-invalid @enderror" placeholder="{{ __('camps.hints.note') }}">
                            </div>
                            <div class="col-md-2 d-grid">
                                <button type="submit" class="btn btn-success">{{ __('camps.actions.check_in') }}</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-centered table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('camps.fields.worker_id') }}</th>
                                <th>{{ __('workers.fields.country_id') }}</th>
                                <th>{{ __('workers.fields.status') }}</th>
                                <th>{{ __('camps.fields.today') }}</th>
                                @if ($canMove)<th>{{ __('camps.move_title') }}</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($workers as $worker)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.workers.show', $worker) }}" class="fw-semibold">{{ $worker->trans('name') }}</a>
                                        <div class="text-muted font-size-12" dir="ltr">{{ $worker->number() }}</div>
                                    </td>
                                    <td>{{ $worker->country?->trans('nationality') }} · <span class="text-muted">{{ $worker->profession?->trans('name') }}</span></td>
                                    <td><x-worker-status :status="$worker->status" /></td>
                                    <td>
                                        @if ($row = $attendance->get($worker->id))
                                            <span class="badge bg-{{ $row->status->color() }}-subtle text-{{ $row->status->color() }}">{{ $row->status->label() }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    @if ($canMove)
                                        <td>
                                            <form method="POST" action="{{ route('admin.camps.move', [$camp, $worker]) }}" class="d-flex gap-1"
                                                data-confirm="{{ __('camps.move_title') }}?" data-confirm-text="{{ $worker->trans('name') }}">
                                                @csrf
                                                <select name="to_camp_id" class="form-select form-select-sm" style="min-width: 140px;">
                                                    <option value="">{{ __('camps.check_out_option') }}</option>
                                                    @foreach ($otherCamps as $other)
                                                        <option value="{{ $other->id }}">→ {{ $other->trans('name') }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="text" name="note" maxlength="500" required class="form-control form-control-sm" placeholder="{{ __('camps.fields.note') }}" style="min-width: 140px;">
                                                <button type="submit" class="btn btn-sm btn-outline-warning"><i class="ri-arrow-left-right-line"></i></button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">{{ __('camps.empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @if ($tab === 'attendance')
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.camps.show', $camp) }}" class="row g-2 align-items-end mb-3">
                    <input type="hidden" name="tab" value="attendance">
                    <div class="col-md-3">
                        <label class="form-label" for="date_filter">{{ __('camps.fields.date') }}</label>
                        <input type="date" name="date" id="date_filter" max="{{ today()->toDateString() }}" value="{{ $date->toDateString() }}" class="form-control" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-9 text-muted font-size-13">
                        {{ $attendance->isEmpty() ? __('camps.attendance_not_taken') : __('camps.attendance_taken', ['count' => $attendance->count(), 'by' => $attendance->first()->recorder?->displayName() ?? '—']) }}
                    </div>
                </form>

                @if ($workers->isEmpty() && $attendance->isEmpty())
                    <p class="text-center text-muted py-3 mb-0">{{ __('camps.empty') }}</p>
                @else
                    <form method="POST" action="{{ route('admin.camps.attendance', $camp) }}">
                        @csrf
                        <input type="hidden" name="date" value="{{ $date->toDateString() }}">
                        @if ($canMove)
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <button type="button" class="btn btn-sm btn-outline-success" id="all-present"><i class="ri-check-double-line me-1"></i>{{ __('camps.actions.all_present') }}</button>
                            </div>
                        @endif
                        <div class="table-responsive">
                            <table class="table table-centered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('camps.fields.worker_id') }}</th>
                                        <th>{{ __('camps.fields.status') }}</th>
                                        <th>{{ __('camps.fields.note') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($workers as $worker)
                                        @php
                                            $current = old("rows.{$worker->id}.status", $attendance->get($worker->id)?->status->value);
                                        @endphp
                                        <tr>
                                            <td>{{ $worker->trans('name') }} <span class="text-muted font-size-12" dir="ltr">{{ $worker->number() }}</span></td>
                                            <td class="text-nowrap">
                                                @foreach ($statuses as $status)
                                                    <input type="radio" class="btn-check attendance-{{ $status->value }}" name="rows[{{ $worker->id }}][status]" id="att-{{ $worker->id }}-{{ $status->value }}" value="{{ $status->value }}" @checked($current === $status->value) @disabled(! $canMove)>
                                                    <label class="btn btn-sm btn-outline-{{ $status->color() }}" for="att-{{ $worker->id }}-{{ $status->value }}">{{ $status->label() }}</label>
                                                @endforeach
                                            </td>
                                            <td><input type="text" name="rows[{{ $worker->id }}][note]" maxlength="500" value="{{ old("rows.{$worker->id}.note", $attendance->get($worker->id)?->note) }}" class="form-control form-control-sm" @disabled(! $canMove)></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($canMove)
                            <div class="d-flex justify-content-end mt-3">
                                <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>{{ __('camps.actions.save_attendance') }}</button>
                            </div>
                        @endif
                    </form>
                @endif
            </div>
        </div>
    @endif

    @if ($tab === 'movements')
        @include('admin.camps._movements-table', ['movements' => $movements, 'showWorker' => true])
    @endif
@endsection

@push('scripts')
    <script>
        document.getElementById('all-present')?.addEventListener('click', function () {
            document.querySelectorAll('.attendance-present').forEach(function (radio) { radio.checked = true; });
        });
    </script>
@endpush
