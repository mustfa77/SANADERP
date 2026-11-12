{{-- Worker profile: current housing and the housing movements. --}}
<p class="mb-3">
    {{ __('camps.current') }}:
    @if ($worker->camp)
        @can('camps.view')
            <a href="{{ route('admin.camps.show', $worker->camp) }}" class="fw-semibold">{{ $worker->camp->trans('name') }}</a>
        @else
            <strong>{{ $worker->camp->trans('name') }}</strong>
        @endcan
    @else
        <span class="text-muted">{{ __('camps.not_housed') }}</span>
    @endif
</p>

@include('admin.camps._movements-table', ['movements' => $worker->campMovements()->with(['fromCamp', 'toCamp', 'creator'])->limit(100)->get(), 'showWorker' => false])
