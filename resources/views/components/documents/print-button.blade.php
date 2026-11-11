{{-- "Print authority file" button (App\View\Components\Documents\PrintButton). --}}
@can('documents.print_authority_file')
    @php
        $label = __('documents.print_authority_file', ['authority' => $authorityModel->trans('name')]);
    @endphp

    @if ($missing === [])
        <form method="POST" action="{{ route('admin.documents.print', [$ownerAlias, $owner->getKey(), $authorityModel]) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-success"><i class="ri-printer-line me-1"></i>{{ $label }}</button>
        </form>
    @else
        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#{{ $modalId() }}">
            <i class="ri-printer-line me-1"></i>{{ $label }}
            <span class="badge bg-danger ms-1">{{ count($missing) }}</span>
        </button>

        <div class="modal fade" id="{{ $modalId() }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-error-warning-line text-danger me-1"></i>{{ __('documents.missing_title') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <p>{{ __('documents.missing_intro', ['authority' => $authorityModel->trans('name')]) }}</p>
                        <ul class="mb-0">
                            @foreach ($missing as $name)
                                <li class="text-danger">{{ $name }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.close') }}</button>
                    </div>
                </div>
            </div>
        </div>

        @if ($shouldOpenModal())
            @push('scripts')
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        bootstrap.Modal.getOrCreateInstance(document.getElementById(@json($modalId()))).show();
                    });
                </script>
            @endpush
        @endif
    @endif
@endcan
