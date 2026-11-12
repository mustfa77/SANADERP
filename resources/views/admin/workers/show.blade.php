@extends('layouts.app')

@section('title', $worker->trans('name'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('workers.show'), 'parent' => __('workers.title')])

    <x-alerts :flash="false" />

    <div class="row">
        {{-- Worker card --}}
        <div class="col-xl-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="d-flex justify-content-center mb-3">
                        @include('admin.workers._avatar', ['worker' => $worker, 'size' => 'lg'])
                    </div>
                    <h5 class="mb-1">{{ $worker->trans('name') }}</h5>
                    <p class="text-muted mb-2">{{ $worker->country?->trans('nationality') }} · {{ $worker->profession?->trans('name') }}</p>
                    <x-worker-status :status="$worker->status" />
                    <div class="text-muted font-size-12 mt-1">
                        {{ __('workers.status_since', ['date' => $worker->status_changed_at?->format('Y-m-d') ?? '—']) }}
                    </div>

                    <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                        @can('update', $worker)
                            <a href="{{ route('admin.workers.edit', $worker) }}" class="btn btn-sm btn-success"><i class="ri-pencil-line me-1"></i>{{ __('common.edit') }}</a>
                        @endcan
                        @can('changeStatus', $worker)
                            @if ($manualTransitions !== [])
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#change-status-modal">
                                    <i class="ri-exchange-line me-1"></i>{{ __('workers.change_status') }}
                                </button>
                            @endif
                        @endcan
                    </div>
                </div>
                <div class="card-body border-top">
                    <dl class="row mb-0 font-size-13">
                        <dt class="col-6 text-muted fw-normal">{{ __('workers.fields.branch_id') }}</dt>
                        <dd class="col-6">{{ $worker->branch?->trans('name') }}</dd>
                        <dt class="col-6 text-muted fw-normal">{{ __('workers.fields.camp_id') }}</dt>
                        <dd class="col-6">{{ $worker->camp?->trans('name') ?? '—' }}</dd>
                        <dt class="col-6 text-muted fw-normal">{{ __('workers.fields.sponsor_type') }}</dt>
                        <dd class="col-6">{{ $worker->sponsor_type->label() }}</dd>
                        <dt class="col-6 text-muted fw-normal">{{ __('workers.fields.passport_no') }}</dt>
                        <dd class="col-6"><x-masked :value="$worker->passport_no" permission="workers.view_sensitive" type="passport" /></dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Tabs from config/profile_tabs.php --}}
        <div class="col-xl-9">
            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
                        @foreach ($tabs as $tab)
                            <li class="nav-item">
                                <a class="nav-link @if ($activeTab === $tab['key']) active @endif" data-bs-toggle="tab" href="#tab-{{ $tab['key'] }}" role="tab">
                                    <i class="{{ $tab['icon'] }} me-1 align-middle"></i> <span class="d-none d-md-inline-block">{{ __($tab['label']) }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <div class="tab-content pt-3">
                        @foreach ($tabs as $tab)
                            <div class="tab-pane @if ($activeTab === $tab['key']) active @endif" id="tab-{{ $tab['key'] }}" role="tabpanel">
                                @include($tab['view'], ['worker' => $worker])
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('changeStatus', $worker)
        @if ($manualTransitions !== [])
            @include('admin.workers._status-modal')
        @endif
    @endcan
@endsection
