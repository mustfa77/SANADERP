@props(['action'])
{{-- Up / down buttons that change sort_order (PATCH {action} with direction). --}}
<form method="POST" action="{{ $action }}" class="d-inline-flex gap-1">
    @csrf
    @method('PATCH')
    <button type="submit" name="direction" value="up" class="btn btn-sm btn-light px-2" title="{{ __('reference.move_up') }}" data-bs-toggle="tooltip">
        <i class="ri-arrow-up-s-line"></i>
    </button>
    <button type="submit" name="direction" value="down" class="btn btn-sm btn-light px-2" title="{{ __('reference.move_down') }}" data-bs-toggle="tooltip">
        <i class="ri-arrow-down-s-line"></i>
    </button>
</form>
