{{-- Ordered document types ($documentTypes). Order = upload screen and print order. --}}
@php($canManage = auth()->user()->can('document_types.manage'))
<div class="table-responsive">
    <table class="table table-centered table-hover mb-0">
        <thead class="table-light">
            <tr>
                @if ($canManage)
                    <th style="width: 90px;">{{ __('reference.order') }}</th>
                @endif
                <th style="width: 40px;">#</th>
                <th>{{ __('reference.fields.name') }}</th>
                <th>{{ __('reference.fields.profession_id') }}</th>
                <th>{{ __('reference.fields.authority_id') }}</th>
                <th class="text-center">{{ __('reference.fields.is_required') }}</th>
                <th class="text-center">{{ __('reference.fields.include_in_authority_file') }}</th>
                <th>{{ __('reference.fields.is_active') }}</th>
                @if ($canManage)
                    <th class="text-end">{{ __('common.actions') }}</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($documentTypes as $documentType)
                <tr @class(['text-muted' => ! $documentType->is_active])>
                    @if ($canManage)
                        <td class="text-nowrap"><x-reference.move-buttons :action="route('admin.document-types.move', $documentType)" /></td>
                    @endif
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $documentType->trans('name') }}</td>
                    <td>{{ $documentType->profession?->trans('name') ?? __('reference.all_professions') }}</td>
                    <td>{{ $documentType->authority?->trans('name') ?? '—' }}</td>
                    <td class="text-center">
                        @if ($documentType->is_required)
                            <span class="badge bg-danger-subtle text-danger">{{ __('reference.required') }}</span>
                        @else
                            <span class="text-muted">{{ __('reference.optional') }}</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <i class="{{ $documentType->include_in_authority_file ? 'ri-checkbox-circle-fill text-success' : 'ri-close-circle-line text-muted' }} font-size-18"></i>
                    </td>
                    <td><x-reference.active-badge :active="$documentType->is_active" /></td>
                    @if ($canManage)
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.document-types.edit', $documentType) }}" class="btn btn-sm btn-outline-success" title="{{ __('common.edit') }}" data-bs-toggle="tooltip"><i class="ri-pencil-line"></i></a>
                            <x-action-form :action="route('admin.document-types.toggle', $documentType)" method="PATCH"
                                :button-class="$documentType->is_active ? 'btn btn-sm btn-outline-warning' : 'btn btn-sm btn-outline-info'"
                                :title="$documentType->is_active ? __('reference.deactivate') : __('reference.activate')">
                                <i class="{{ $documentType->is_active ? 'ri-pause-circle-line' : 'ri-play-circle-line' }}"></i>
                            </x-action-form>
                            <x-action-form :action="route('admin.document-types.destroy', $documentType)" method="DELETE"
                                :confirm="__('common.are_you_sure')" :title="__('common.delete')">
                                <i class="ri-delete-bin-line"></i>
                            </x-action-form>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">{{ __('reference.no_document_types') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<p class="text-muted font-size-13 mt-2 mb-0"><i class="ri-information-line me-1"></i>{{ __('reference.document_types_hint') }}</p>
