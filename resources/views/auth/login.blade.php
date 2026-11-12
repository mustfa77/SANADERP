@extends('layouts.auth')

@section('title', __('auth.login.title'))

@section('content')
    <h4 class="font-size-18 text-muted mt-2 text-center">{{ __('auth.login.welcome') }}</h4>
    <p class="mb-5 text-center">{{ __('auth.login.subtitle', ['app' => $site->brand()]) }}</p>

    <x-alerts :summary="false" />

    @php
        $demo = config('auth.demo_login');
    @endphp
    @if (filled($demo['email'] ?? null) && filled($demo['password'] ?? null))
        <div class="alert alert-info mb-4" id="demo-login">
            <div class="fw-semibold mb-2"><i class="ri-information-line me-1"></i>{{ __('auth.login.demo.title') }}</div>
            <div class="font-size-13">{{ __('auth.login.email') }}: <span dir="ltr" class="user-select-all">{{ $demo['email'] }}</span></div>
            <div class="font-size-13 mb-2">{{ __('auth.login.password') }}: <span dir="ltr" class="user-select-all">{{ $demo['password'] }}</span></div>
            <button type="button" class="btn btn-sm btn-info" id="demo-login-fill"
                data-email="{{ $demo['email'] }}" data-password="{{ $demo['password'] }}">{{ __('auth.login.demo.fill') }}</button>
        </div>
        <script>
            document.getElementById('demo-login-fill').addEventListener('click', function () {
                document.getElementById('email').value = this.dataset.email;
                document.getElementById('password').value = this.dataset.password;
                document.getElementById('password').closest('form').querySelector('button[type=submit]').focus();
            });
        </script>
    @endif

    <form class="form-horizontal" method="POST" action="{{ route('login.store') }}" novalidate>
        @csrf

        <div class="row">
            <div class="col-md-12">
                <div class="mb-4">
                    <label class="form-label" for="email">{{ __('auth.login.email') }}</label>
                    <input type="email" name="email" id="email" dir="ltr" value="{{ old('email') }}" required autofocus
                        autocomplete="username" class="form-control @error('email') is-invalid @enderror"
                        placeholder="{{ __('auth.login.email_placeholder') }}">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">{{ __('auth.login.password') }}</label>
                    <input type="password" name="password" id="password" dir="ltr" required autocomplete="current-password"
                        class="form-control @error('password') is-invalid @enderror"
                        placeholder="{{ __('auth.login.password_placeholder') }}">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col">
                        <div class="form-check">
                            <input type="checkbox" name="remember" value="1" class="form-check-input" id="remember" @checked(old('remember'))>
                            <label class="form-check-label" for="remember">{{ __('auth.login.remember') }}</label>
                        </div>
                    </div>
                    <div class="col-7">
                        <div class="text-md-end mt-3 mt-md-0">
                            <a href="{{ route('password.request') }}" class="text-muted"><i class="mdi mdi-lock"></i> {{ __('auth.login.forgot') }}</a>
                        </div>
                    </div>
                </div>
                <div class="d-grid mt-4">
                    <button class="btn btn-primary waves-effect waves-light" type="submit">{{ __('auth.login.submit') }}</button>
                </div>
            </div>
        </div>
    </form>
@endsection
