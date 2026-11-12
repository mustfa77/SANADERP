@extends('layouts.app')

@section('title', __('cases.title'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('cases.title')])

    <div class="card">
        <div class="card-body">
            <p class="text-muted font-size-13">{{ __('cases.hint') }}</p>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <ul class="nav nav-tabs nav-tabs-custom">
                    @foreach ([...\App\Enums\CaseStatus::cases(), null] as $option)
                        <li class="nav-item">
                            <a class="nav-link @if ($status === $option) active @endif" href="{{ route('admin.cases.index', ['status' => $option?->value ?? 'all', 'search' => $search]) }}">
                                {{ $option?->label() ?? __('common.all') }}
                                @if ($option)
                                    <span class="badge {{ $status === $option ? 'bg-primary' : 'bg-light text-muted' }} ms-1">{{ $counts[$option->value] ?? 0 }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
                <form method="GET" action="{{ route('admin.cases.index') }}" class="d-flex gap-2">
                    <input type="hidden" name="status" value="{{ $status?->value ?? 'all' }}">
                    <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="{{ __('cases.search_placeholder') }}">
                    <button type="submit" class="btn btn-outline-primary"><i class="ri-search-line"></i></button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-centered table-nowrap table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('cases.fields.number') }}</th>
                            <th>{{ __('cases.fields.worker_id') }}</th>
                            <th>{{ __('cases.fields.reason') }}</th>
                            <th>{{ __('cases.fields.opened_at') }}</th>
                            <th>{{ __('cases.fields.decision') }}</th>
                            <th>{{ __('cases.fields.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cases as $case)
                            <tr>
                                <td><a href="{{ route('admin.cases.show', $case) }}" class="fw-semibold"><code dir="ltr">{{ $case->number }}</code></a></td>
                                <td>
                                    {{ $case->worker?->trans('name') }}
                                    <div class="text-muted font-size-12">{{ $case->worker?->country?->trans('nationality') }} · <x-worker-status :status="$case->worker->status" /></div>
                                </td>
                                <td class="text-wrap font-size-13" style="max-width: 280px;">{{ $case->reason }}</td>
                                <td>
                                    <span dir="ltr">{{ $case->opened_at->format('Y-m-d') }}</span>
                                    <div class="font-size-12 {{ $case->status === \App\Enums\CaseStatus::Open && $case->ageInDays() > 3 ? 'text-danger' : 'text-muted' }}">{{ __('cases.days', ['count' => $case->ageInDays()]) }}</div>
                                </td>
                                <td>{{ $case->decision?->label() ?? '—' }}</td>
                                <td><span class="badge bg-{{ $case->status->color() }}-subtle text-{{ $case->status->color() }}">{{ $case->status->label() }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">{{ __('cases.none') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $cases->links() }}</div>
        </div>
    </div>
@endsection
