{{-- One-click "add to monthly / hourly rental" (or "remove from rental") for $worker. Expects $type, $style, $icon. --}}
<form method="POST" action="{{ route('admin.rental.workers.assign', $worker) }}" class="d-inline"
    @if ($type === 'none') data-confirm="{{ __('rental.pool.actions.none') }}?" data-confirm-text="{{ __('rental.pool.confirm_remove', ['name' => $worker->trans('name')]) }}" @endif>
    @csrf
    @method('PATCH')
    <input type="hidden" name="rental_type" value="{{ $type }}">
    <button type="submit" class="btn btn-sm {{ $style }}" title="{{ __('rental.pool.actions.'.$type) }}">
        <i class="{{ $icon }}{{ $type === 'none' ? '' : ' me-1' }}"></i>@unless ($type === 'none'){{ __('rental.pool.actions.'.$type) }}@endunless
    </button>
</form>
