{{-- Worker CV tab: summary, edit and print. --}}
<div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
    @can('update', $worker)
        <a href="{{ route('admin.workers.cv.edit', $worker) }}" class="btn btn-sm btn-outline-primary"><i class="ri-pencil-line me-1"></i>{{ __('cv.edit') }}</a>
    @endcan
    <a href="{{ route('admin.workers.cv.preview', $worker) }}" target="_blank" rel="noopener" class="btn btn-sm btn-success"><i class="ri-file-user-line me-1"></i>{{ __('cv.print') }}</a>
</div>

@include('workers._cv-summary', [
    'photoUrl' => $worker->photo_path ? route('admin.workers.photo', $worker) : null,
    'fullPhotoUrl' => $worker->full_photo_path ? route('admin.workers.full-photo', $worker) : null,
])
