@extends('layouts.app')

@section('title', __('profile.title'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('profile.title')])

    @php($roleRequires2fa = $user->roles->contains('requires_2fa', true))

    <div class="row">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body text-center">
                    <img src="{{ $user->avatarUrl() }}" alt="" class="avatar-lg rounded-circle img-thumbnail mb-3">
                    <h5 class="mb-1">{{ $user->displayName() }}</h5>
                    <p class="text-muted mb-2" dir="ltr">{{ $user->email }}</p>
                    @foreach ($user->roles as $role)
                        <span class="badge bg-primary-subtle text-primary font-size-12">{{ $role->displayName() }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-tabs nav-tabs-custom nav-justified" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{ $errors->hasAny(['current_password', 'password']) || ! $errors->any() ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-password" role="tab">
                                <i class="ri-lock-password-line me-1 align-middle"></i> <span class="d-none d-md-inline-block">{{ __('profile.tabs.password') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab-language" role="tab">
                                <i class="ri-global-line me-1 align-middle"></i> <span class="d-none d-md-inline-block">{{ __('profile.tabs.language') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab-2fa" role="tab">
                                <i class="ri-shield-check-line me-1 align-middle"></i> <span class="d-none d-md-inline-block">{{ __('profile.tabs.two_factor') }}</span>
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content p-3">
                        <div class="tab-pane active" id="tab-password" role="tabpanel">
                            <form method="POST" action="{{ route('admin.profile.password') }}" novalidate>
                                @csrf
                                @method('PUT')
                                <div class="mb-4">
                                    <label class="form-label" for="current_password">{{ __('auth.force.current_password') }}</label>
                                    <input type="password" name="current_password" id="current_password" dir="ltr" autocomplete="current-password"
                                        class="form-control @error('current_password') is-invalid @enderror">
                                    @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                @include('auth.partials.new-password', ['passwordLabel' => __('auth.reset.password'), 'confirmationLabel' => __('auth.reset.password_confirmation')])
                                <button type="submit" class="btn btn-primary">{{ __('profile.change_password') }}</button>
                            </form>
                        </div>

                        <div class="tab-pane" id="tab-language" role="tabpanel">
                            <form method="POST" action="{{ route('admin.profile.language') }}">
                                @csrf
                                @method('PUT')
                                <div class="mb-4">
                                    <label class="form-label" for="preferred_language">{{ __('users.fields.preferred_language') }}</label>
                                    <select name="preferred_language" id="preferred_language" class="form-select">
                                        @foreach (config('app.available_locales') as $code => $locale)
                                            <option value="{{ $code }}" @selected($user->preferred_language === $code)>{{ $locale['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">{{ __('common.save') }}</button>
                            </form>
                        </div>

                        <div class="tab-pane" id="tab-2fa" role="tabpanel">
                            @if ($roleRequires2fa)
                                <div class="alert alert-info">{{ __('profile.two_factor_required') }}</div>
                            @endif
                            <form method="POST" action="{{ route('admin.profile.two-factor') }}">
                                @csrf
                                @method('PUT')
                                <div class="form-check form-switch mb-4">
                                    <input type="hidden" name="two_factor_enabled" value="0">
                                    <input type="checkbox" name="two_factor_enabled" value="1" id="two_factor_enabled" class="form-check-input"
                                        @checked($user->two_factor_enabled || $roleRequires2fa) @disabled($roleRequires2fa)>
                                    <label class="form-check-label" for="two_factor_enabled">{{ __('profile.two_factor_enable') }}</label>
                                </div>
                                @unless ($roleRequires2fa)
                                    <button type="submit" class="btn btn-primary">{{ __('common.save') }}</button>
                                @endunless
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
