@extends('layouts.app')

@section('title', __('refunds.title'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('refunds.title')])

    <div class="card">
        <div class="card-body">
            <ul class="nav nav-tabs nav-tabs-custom mb-3">
                @foreach ([null, ...\App\Enums\RefundStatus::cases()] as $option)
                    <li class="nav-item">
                        <a class="nav-link @if ($status === $option) active @endif" href="{{ route('admin.refunds.index', ['status' => $option?->value ?? 'all']) }}">
                            {{ $option?->label() ?? __('common.all') }}
                            @if ($option === \App\Enums\RefundStatus::Pending && $pendingCount)
                                <span class="badge bg-warning ms-1">{{ $pendingCount }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>

            @if ($refunds->isEmpty())
                <p class="text-center text-muted py-4 mb-0">{{ __('refunds.none') }}</p>
            @else
                @include('admin.refunds._table', ['refunds' => $refunds, 'showPayable' => true])
                {{ $refunds->links() }}
            @endif
        </div>
    </div>
@endsection
