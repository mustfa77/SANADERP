@extends('layouts.app')

@section('title', __('audit.show'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('audit.show'), 'parent' => __('audit.title')])

    <div class="row">
        <div class="col-xl-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">{{ $log->actionLabel() }}</h4>
                    <p class="mb-4">{{ $log->description() }}</p>

                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr><th class="w-50">{{ __('audit.fields.date') }}</th><td><span dir="ltr">{{ $log->created_at->format('Y-m-d H:i:s') }}</span></td></tr>
                            <tr>
                                <th>{{ __('audit.fields.user') }}</th>
                                <td>
                                    @if ($log->user && ! $log->user->trashed())
                                        <a href="{{ route('admin.users.show', $log->user) }}">{{ $log->user->displayName() }}</a>
                                    @else
                                        {{ $log->user?->displayName() ?? __('audit.system') }}
                                    @endif
                                </td>
                            </tr>
                            <tr><th>{{ __('audit.fields.target') }}</th><td>{{ $log->targetTypeLabel() ?? '—' }} @if ($log->target_id)<span dir="ltr">#{{ $log->target_id }}</span>@endif</td></tr>
                            <tr><th>{{ __('audit.fields.ip') }}</th><td><span dir="ltr">{{ $log->ip_address }}</span></td></tr>
                            <tr><th>{{ __('audit.fields.user_agent') }}</th><td class="text-break font-size-12" dir="ltr">{{ $log->user_agent }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">{{ __('audit.fields.old_values') }} / {{ __('audit.fields.new_values') }}</h4>
                    @if ($log->old_values || $log->new_values)
                        @include('admin.audit-logs._changes', ['log' => $log])
                    @else
                        <p class="text-muted mb-0">{{ __('common.none') }}</p>
                    @endif
                </div>
            </div>

            <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-light">{{ __('common.back') }}</a>
        </div>
    </div>
@endsection
