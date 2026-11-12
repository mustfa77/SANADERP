@extends('layouts.app')

@section('title', $country->trans('name'))

@section('content')
    @include('layouts.partials.page-title', ['title' => $country->trans('name'), 'parent' => __('reference.resources.countries.title')])

    @php($canManage = auth()->user()->can('settings.manage'))

    <div class="row">
        <div class="col-xl-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar-md mx-auto mb-3">
                        <span class="avatar-title rounded-circle font-size-20" dir="ltr">{{ $country->code ?? '—' }}</span>
                    </div>
                    <h5 class="mb-1">{{ $country->trans('name') }}</h5>
                    <p class="text-muted mb-2">{{ __('reference.fields.nationality') }}: {{ $country->trans('nationality') }}</p>
                    <x-reference.active-badge :active="$country->is_active" />

                    <div class="d-flex justify-content-around border-top mt-3 pt-3">
                        <div>
                            <h5 class="mb-0">{{ $country->agencies_count }}</h5>
                            <small class="text-muted">{{ __('reference.resources.agencies.title') }}</small>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $country->authorities_count }}</h5>
                            <small class="text-muted">{{ __('reference.resources.authorities.title') }}</small>
                        </div>
                    </div>

                    @if ($canManage)
                        <a href="{{ route('admin.reference.countries.edit', $country) }}" class="btn btn-sm btn-success mt-3">
                            <i class="ri-pencil-line me-1"></i>{{ __('common.edit') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-9">
            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link @if ($tab === 'stages') active @endif" data-bs-toggle="tab" href="#tab-stages" role="tab">
                                <i class="ri-route-line me-1 align-middle"></i> {{ __('reference.tabs.stages') }}
                                <span class="badge bg-light text-body ms-1">{{ $stages->count() }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link @if ($tab === 'documents') active @endif" data-bs-toggle="tab" href="#tab-documents" role="tab">
                                <i class="ri-file-list-3-line me-1 align-middle"></i> {{ __('reference.tabs.documents') }}
                                <span class="badge bg-light text-body ms-1">{{ $documentTypes->count() }}</span>
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content pt-3">
                        {{-- Travel stages --}}
                        <div class="tab-pane @if ($tab === 'stages') active @endif" id="tab-stages" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <p class="text-muted mb-0">{{ __('reference.stages_hint') }}</p>
                                @if ($canManage)
                                    <a href="{{ route('admin.countries.stages.create', $country) }}" class="btn btn-sm btn-primary">
                                        <i class="ri-add-line me-1"></i>{{ __('reference.add', ['item' => __('reference.resources.stages.singular')]) }}
                                    </a>
                                @endif
                            </div>
                            <div class="table-responsive">
                                <table class="table table-centered table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            @if ($canManage)
                                                <th style="width: 90px;">{{ __('reference.order') }}</th>
                                            @endif
                                            <th style="width: 40px;">#</th>
                                            <th>{{ __('reference.fields.name') }}</th>
                                            <th>{{ __('reference.fields.max_days') }}</th>
                                            <th>{{ __('reference.fields.is_active') }}</th>
                                            @if ($canManage)
                                                <th class="text-end">{{ __('common.actions') }}</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($stages as $stage)
                                            <tr @class(['text-muted' => ! $stage->is_active])>
                                                @if ($canManage)
                                                    <td class="text-nowrap"><x-reference.move-buttons :action="route('admin.countries.stages.move', [$country, $stage])" /></td>
                                                @endif
                                                <td>{{ $loop->iteration }}</td>
                                                <td class="fw-semibold">{{ $stage->trans('name') }}</td>
                                                <td>{{ $stage->max_days ? __('reference.days', ['count' => $stage->max_days]) : '—' }}</td>
                                                <td><x-reference.active-badge :active="$stage->is_active" /></td>
                                                @if ($canManage)
                                                    <td class="text-end text-nowrap">
                                                        <a href="{{ route('admin.countries.stages.edit', [$country, $stage]) }}" class="btn btn-sm btn-outline-success" title="{{ __('common.edit') }}" data-bs-toggle="tooltip"><i class="ri-pencil-line"></i></a>
                                                        <x-action-form :action="route('admin.countries.stages.toggle', [$country, $stage])" method="PATCH"
                                                            :button-class="$stage->is_active ? 'btn btn-sm btn-outline-warning' : 'btn btn-sm btn-outline-info'"
                                                            :title="$stage->is_active ? __('reference.deactivate') : __('reference.activate')">
                                                            <i class="{{ $stage->is_active ? 'ri-pause-circle-line' : 'ri-play-circle-line' }}"></i>
                                                        </x-action-form>
                                                        <x-action-form :action="route('admin.countries.stages.destroy', [$country, $stage])" method="DELETE"
                                                            :confirm="__('common.are_you_sure')" :title="__('common.delete')">
                                                            <i class="ri-delete-bin-line"></i>
                                                        </x-action-form>
                                                    </td>
                                                @endif
                                            </tr>
                                        @empty
                                            <tr><td colspan="6" class="text-center text-muted py-4">{{ __('reference.no_stages') }}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Documents --}}
                        <div class="tab-pane @if ($tab === 'documents') active @endif" id="tab-documents" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <p class="text-muted mb-0">
                                    {{ __('reference.country_documents_hint') }}
                                    <a href="{{ route('admin.document-types.index') }}">{{ __('reference.global_document_types') }}</a>
                                </p>
                                @can('document_types.manage')
                                    <a href="{{ route('admin.document-types.create', ['country' => $country->id]) }}" class="btn btn-sm btn-primary">
                                        <i class="ri-add-line me-1"></i>{{ __('reference.add', ['item' => __('reference.resources.document_types.singular')]) }}
                                    </a>
                                @endcan
                            </div>
                            @include('admin.reference.document-types._table')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
