{{-- Ordered document checklist of an owner (App\View\Components\Documents\Checklist). --}}
@php
    $user = auth()->user();
    $canView = $user->can('documents.view');
    $canUpload = $user->can('documents.upload');
    $canReplace = $user->can('documents.replace');
    $canDeleteVersion = $user->can('documents.delete_version');
    $ownerId = $owner->getKey();
    $accept = '.'.implode(',.', config('documents.mimes'));
    $uploadedCount = $items->filter->isUploaded()->count();
@endphp

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h4 class="card-title mb-0">
                <i class="ri-file-list-3-line me-1"></i>{{ __('documents.checklist') }}
                <span class="text-muted font-size-14">({{ $uploadedCount }} / {{ $items->count() }})</span>
            </h4>
            <small class="text-muted">{{ __('documents.allowed_files', ['types' => strtoupper(implode(', ', config('documents.mimes'))), 'size' => $maxUploadMb()]) }}</small>
        </div>

        <div class="table-responsive">
            <table class="table table-centered mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>{{ __('documents.fields.document') }}</th>
                        <th>{{ __('documents.fields.status') }}</th>
                        <th>{{ __('documents.fields.uploaded') }}</th>
                        <th class="text-end">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        @php
                            $file = $item->latestFile();
                            $errorKey = 'document_'.$item->type->id;
                            $versions = $item->document?->files ?? collect();
                        @endphp
                        <tr @class(['table-danger' => $item->isRequired() && ! $item->isUploaded(), 'text-muted' => ! $item->type->is_active])>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <span class="fw-semibold">{{ $item->name() }}</span>
                                @if ($item->isRequired())
                                    <span class="badge bg-danger-subtle text-danger ms-1">{{ __('reference.required') }}</span>
                                @endif
                                @unless ($item->type->is_active)
                                    <span class="badge bg-secondary-subtle text-secondary ms-1">{{ __('common.inactive') }}</span>
                                @endunless
                            </td>
                            <td>
                                @if ($item->isUploaded())
                                    <span class="badge bg-success font-size-12"><i class="ri-check-line"></i> {{ __('documents.status.uploaded') }}</span>
                                    @if ($file->version > 1)
                                        <span class="badge bg-light text-body font-size-11">{{ __('documents.version_short', ['version' => $file->version]) }}</span>
                                    @endif
                                @else
                                    <span class="badge {{ $item->isRequired() ? 'bg-danger' : 'bg-secondary' }} font-size-12">{{ __('documents.status.missing') }}</span>
                                @endif
                            </td>
                            <td class="font-size-13">
                                @if ($file)
                                    <span dir="ltr">{{ $file->created_at?->format('Y-m-d H:i') }}</span>
                                    <div class="text-muted">{{ $file->uploader?->displayName() ?? '—' }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex flex-wrap justify-content-end align-items-start gap-1">
                                    @if ($file && $canView)
                                        <a href="{{ route('admin.documents.files.show', $file) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                                            <i class="ri-eye-line"></i> {{ __('documents.view') }}
                                        </a>
                                    @endif
                                    @if (($file && $canReplace) || (! $file && $canUpload))
                                        <form method="POST" action="{{ route('admin.documents.upload', [$ownerAlias, $ownerId, $item->type]) }}" enctype="multipart/form-data" class="d-flex gap-1">
                                            @csrf
                                            <input type="file" name="file" accept="{{ $accept }}" required class="form-control form-control-sm @error($errorKey) is-invalid @enderror" style="max-width: 200px;">
                                            <button type="submit" class="btn btn-sm {{ $file ? 'btn-outline-warning' : 'btn-primary' }} text-nowrap">
                                                <i class="{{ $file ? 'ri-refresh-line' : 'ri-upload-2-line' }}"></i> {{ $file ? __('documents.replace') : __('documents.upload_here') }}
                                            </button>
                                        </form>
                                    @endif
                                    @if ($versions->count() > 1 && $canView)
                                        <button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#versions-{{ $item->type->id }}">
                                            <i class="ri-history-line"></i> {{ __('documents.versions', ['count' => $versions->count()]) }}
                                        </button>
                                    @endif
                                </div>
                                @error($errorKey) <div class="text-danger font-size-12 mt-1">{{ $message }}</div> @enderror
                            </td>
                        </tr>
                        @if ($versions->count() > 1 && $canView)
                            <tr class="collapse" id="versions-{{ $item->type->id }}">
                                <td></td>
                                <td colspan="4">
                                    @include('components.documents._versions', ['versions' => $versions, 'canDeleteVersion' => $canDeleteVersion])
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">{{ __('reference.no_document_types') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Extra documents --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-4 mb-2">
            <h5 class="font-size-15 mb-0">{{ __('documents.extra_documents') }}</h5>
            @if ($canUpload)
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#extra-document-form-{{ $ownerId }}">
                    <i class="ri-add-line"></i> {{ __('documents.add_extra') }}
                </button>
            @endif
        </div>

        @if ($canUpload)
            <div class="collapse {{ $errors->extraDocument->any() ? 'show' : '' }}" id="extra-document-form-{{ $ownerId }}">
                <form method="POST" action="{{ route('admin.documents.extra', [$ownerAlias, $ownerId]) }}" enctype="multipart/form-data" class="row g-2 mb-3">
                    @csrf
                    <div class="col-md-5">
                        <input type="text" name="title" value="{{ old('title') }}" maxlength="255" placeholder="{{ __('documents.fields.title') }}"
                            class="form-control @if ($errors->extraDocument->has('title')) is-invalid @endif">
                        @if ($errors->extraDocument->has('title')) <div class="invalid-feedback">{{ $errors->extraDocument->first('title') }}</div> @endif
                    </div>
                    <div class="col-md-5">
                        <input type="file" name="file" accept="{{ $accept }}" class="form-control @if ($errors->extraDocument->has('file')) is-invalid @endif">
                        @if ($errors->extraDocument->has('file')) <div class="invalid-feedback">{{ $errors->extraDocument->first('file') }}</div> @endif
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary"><i class="ri-upload-2-line"></i> {{ __('documents.upload') }}</button>
                    </div>
                </form>
            </div>
        @endif

        @forelse ($extras as $extra)
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-2 border-bottom">
                <div>
                    <i class="ri-attachment-2 me-1 text-muted"></i><span class="fw-semibold">{{ $extra->title }}</span>
                    <span class="text-muted font-size-13 ms-2" dir="ltr">{{ $extra->latestFile?->created_at?->format('Y-m-d H:i') }}</span>
                    <span class="text-muted font-size-13 ms-1">{{ $extra->latestFile?->uploader?->displayName() }}</span>
                </div>
                @if ($extra->latestFile && $canView)
                    <a href="{{ route('admin.documents.files.show', $extra->latestFile) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                        <i class="ri-eye-line"></i> {{ __('documents.view') }}
                    </a>
                @endif
            </div>
        @empty
            <p class="text-muted mb-0">{{ __('documents.no_extra_documents') }}</p>
        @endforelse
    </div>
</div>
