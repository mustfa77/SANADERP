@extends('layouts.app')

@section('title', __('roles.permissions_of', ['role' => $role->displayName()]))

@php
    $registry = app(\App\Support\PermissionRegistry::class);
    $granted = array_flip($granted);
    $disabled = $readOnly ? 'disabled' : '';
@endphp

@push('styles')
    <style>
        .permission-matrix th, .permission-matrix td { vertical-align: middle; }
        .permission-matrix .section-row td { background: var(--bs-light); font-weight: 600; }
        .permission-matrix .special-perm { white-space: nowrap; }
    </style>
@endpush

@section('content')
    @include('layouts.partials.page-title', ['title' => __('roles.permissions_of', ['role' => $role->displayName()]), 'parent' => __('roles.title')])

    <x-alerts :flash="false" />

    @if ($role->isSuperAdmin())
        <div class="alert alert-info"><i class="ri-shield-star-line me-1"></i>{{ __('roles.matrix.super_admin_locked') }}</div>
    @elseif ($readOnly)
        <div class="alert alert-warning"><i class="ri-eye-line me-1"></i>{{ __('roles.matrix.read_only') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.roles.permissions.update', $role) }}" id="permission-matrix-form">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <h4 class="card-title mb-0">{{ $role->displayName() }} <code class="font-size-13 ms-1" dir="ltr">{{ $role->name }}</code></h4>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="toggle-all" {{ $disabled }}>
                        <label class="form-check-label fw-semibold" for="toggle-all">{{ __('roles.matrix.select_all') }}</label>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm permission-matrix mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width: 200px;">{{ __('roles.matrix.module') }}</th>
                                @foreach ($actions as $action)
                                    <th class="text-center">
                                        <div class="form-check d-inline-flex flex-column align-items-center m-0 p-0">
                                            <input type="checkbox" class="form-check-input m-0 column-toggle" data-action="{{ $action }}"
                                                id="col-{{ $action }}" title="{{ __('roles.matrix.select_all') }}" {{ $disabled }}>
                                            <label class="form-check-label mt-1" for="col-{{ $action }}">{{ __('permissions.actions.'.$action) }}</label>
                                        </div>
                                    </th>
                                @endforeach
                                <th>{{ __('roles.matrix.other') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sections as $section => $modules)
                                <tr class="section-row">
                                    <td colspan="{{ count($actions) + 2 }}">
                                        <div class="form-check m-0">
                                            <input type="checkbox" class="form-check-input section-toggle" data-section="{{ $section }}" id="section-{{ $section }}" {{ $disabled }}>
                                            <label class="form-check-label" for="section-{{ $section }}">{{ __('permissions.sections.'.$section) }}</label>
                                        </div>
                                    </td>
                                </tr>

                                @foreach ($modules as $module => $moduleActions)
                                    <tr>
                                        <td>
                                            <div class="form-check m-0">
                                                <input type="checkbox" class="form-check-input row-toggle" data-module="{{ $module }}"
                                                    id="row-{{ $module }}" title="{{ __('roles.matrix.select_row') }}" {{ $disabled }}>
                                                <label class="form-check-label" for="row-{{ $module }}">{{ __('permissions.modules.'.$module) }}</label>
                                            </div>
                                        </td>

                                        @foreach ($actions as $action)
                                            <td class="text-center">
                                                @if (in_array($action, $moduleActions, true))
                                                    @php($name = "{$module}.{$action}")
                                                    <input type="checkbox" name="permissions[]" value="{{ $name }}" class="form-check-input m-0 perm"
                                                        data-module="{{ $module }}" data-action="{{ $action }}" data-section="{{ $section }}"
                                                        title="{{ $registry->label($name) }}" aria-label="{{ $registry->label($name) }}"
                                                        @checked(isset($granted[$name])) {{ $disabled }}>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        @endforeach

                                        <td>
                                            @foreach (array_diff($moduleActions, $actions) as $special)
                                                @php($name = "{$module}.{$special}")
                                                <div class="form-check special-perm">
                                                    <input type="checkbox" name="permissions[]" value="{{ $name }}" id="perm-{{ $name }}" class="form-check-input perm"
                                                        data-module="{{ $module }}" data-action="{{ $special }}" data-section="{{ $section }}"
                                                        @checked(isset($granted[$name])) {{ $disabled }}>
                                                    <label class="form-check-label" for="perm-{{ $name }}">{{ $registry->label($name) }}</label>
                                                </div>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex gap-2 mt-4">
                    @unless ($readOnly)
                        <button type="submit" class="btn btn-primary waves-effect waves-light"><i class="ri-save-line align-middle me-1"></i> {{ __('common.save') }}</button>
                    @endunless
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-light">{{ __('common.back') }}</a>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function ($) {
            const $form = $('#permission-matrix-form');
            const $perms = $form.find('input.perm');

            // Group toggles: [toggle selector, data attribute linking it to permissions].
            const groups = [
                ['.row-toggle', 'module'],
                ['.column-toggle', 'action'],
                ['.section-toggle', 'section'],
            ];

            function targetsOf(toggle, attribute) {
                return $perms.filter('[data-' + attribute + '="' + $(toggle).data(attribute) + '"]');
            }

            function refresh() {
                groups.forEach(function ([selector, attribute]) {
                    $form.find(selector).each(function () {
                        const $targets = targetsOf(this, attribute);
                        const checked = $targets.filter(':checked').length;
                        this.checked = $targets.length > 0 && checked === $targets.length;
                        this.indeterminate = checked > 0 && checked < $targets.length;
                    });
                });

                const all = $perms.filter(':checked').length;
                const toggleAll = document.getElementById('toggle-all');
                toggleAll.checked = all === $perms.length;
                toggleAll.indeterminate = all > 0 && all < $perms.length;
            }

            groups.forEach(function ([selector, attribute]) {
                $form.on('change', selector, function () {
                    targetsOf(this, attribute).prop('checked', this.checked);
                    refresh();
                });
            });

            $('#toggle-all').on('change', function () {
                $perms.prop('checked', this.checked);
                refresh();
            });

            $perms.on('change', refresh);
            refresh();
        })(jQuery);
    </script>
@endpush
