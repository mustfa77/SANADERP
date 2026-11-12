@extends('layouts.app')

@section('title', $client->trans('name'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('clients.show'), 'parent' => __('clients.title')])

    <div class="row">
        <div class="col-xl-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar-lg mx-auto mb-3">
                        <span class="avatar-title rounded-circle bg-primary-subtle text-primary font-size-24">{{ mb_substr($client->trans('name') ?? '?', 0, 1) }}</span>
                    </div>
                    <h5 class="mb-1">{{ $client->trans('name') }}</h5>
                    <p class="text-muted mb-2" dir="ltr">{{ $client->phone }}</p>
                    <x-reference.active-badge :active="$client->is_active" />

                    <div class="d-flex justify-content-around border-top mt-3 pt-3">
                        <div>
                            <h5 class="mb-0">{{ $client->recruitmentOrders()->count() }}</h5>
                            <small class="text-muted">{{ __('recruitment.tabs.client') }}</small>
                        </div>
                    </div>

                    @can('update', $client)
                        <a href="{{ route('admin.clients.edit', $client) }}" class="btn btn-sm btn-success mt-3"><i class="ri-pencil-line me-1"></i>{{ __('common.edit') }}</a>
                    @endcan
                </div>
            </div>
        </div>

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
                                @include($tab['view'], ['client' => $client])
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
