@props(['action', 'method' => 'POST', 'confirm' => null, 'confirmText' => null, 'buttonClass' => 'btn btn-sm btn-outline-danger', 'title' => null])
{{--
    Small form with one button, optionally confirmed with SweetAlert2
    (see vendor-scripts). Used for delete / activate / deactivate / clone.
--}}
<form method="POST" action="{{ $action }}" class="d-inline"
    @if ($confirm) data-confirm="{{ $confirm }}" data-confirm-text="{{ $confirmText ?? __('common.confirm_delete') }}" @endif>
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif
    <button type="submit" class="{{ $buttonClass }}" @if ($title) title="{{ $title }}" data-bs-toggle="tooltip" @endif>
        {{ $slot }}
    </button>
</form>
