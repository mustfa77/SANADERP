{{-- Version history of one document ($versions newest first). --}}
<table class="table table-sm table-borderless mb-0 font-size-13">
    <thead class="text-muted">
        <tr>
            <th>{{ __('documents.fields.version') }}</th>
            <th>{{ __('documents.fields.file') }}</th>
            <th>{{ __('documents.fields.uploaded') }}</th>
            <th class="text-end">{{ __('common.actions') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($versions as $version)
            <tr>
                <td>
                    {{ $version->version }}
                    @if ($loop->first)
                        <span class="badge bg-success-subtle text-success">{{ __('documents.current') }}</span>
                    @endif
                </td>
                <td dir="ltr" class="text-start">{{ $version->original_name }} <span class="text-muted">({{ \Illuminate\Support\Number::fileSize($version->size, 1) }})</span></td>
                <td><span dir="ltr">{{ $version->created_at?->format('Y-m-d H:i') }}</span> · {{ $version->uploader?->displayName() ?? '—' }}</td>
                <td class="text-end text-nowrap">
                    <a href="{{ route('admin.documents.files.show', $version) }}" target="_blank" rel="noopener" class="btn btn-sm btn-light"><i class="ri-eye-line"></i></a>
                    @if ($canDeleteVersion)
                        <x-action-form :action="route('admin.documents.files.destroy', $version)" method="DELETE"
                            :confirm="__('common.are_you_sure')" :title="__('documents.delete_version')" button-class="btn btn-sm btn-outline-danger">
                            <i class="ri-delete-bin-line"></i>
                        </x-action-form>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
