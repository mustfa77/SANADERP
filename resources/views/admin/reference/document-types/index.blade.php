@extends('layouts.app')

@section('title', __('reference.resources.document_types.title'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('reference.resources.document_types.title'), 'parent' => __('permissions.modules.settings')])

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                <div>
                    <h4 class="card-title mb-1"><i class="ri-file-list-3-line me-1"></i>{{ __('reference.global_document_types') }}</h4>
                    <p class="text-muted mb-0">{{ __('reference.global_document_types_hint') }}</p>
                </div>
                @can('document_types.manage')
                    <a href="{{ route('admin.document-types.create') }}" class="btn btn-primary">
                        <i class="ri-add-line align-middle me-1"></i> {{ __('reference.add', ['item' => __('reference.resources.document_types.singular')]) }}
                    </a>
                @endcan
            </div>
            @include('admin.reference.document-types._table')
        </div>
    </div>
@endsection
