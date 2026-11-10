{{-- New password + confirmation fields with the password rules hint. --}}
<div class="mb-4">
    <label class="form-label" for="password">{{ $passwordLabel }}</label>
    <input type="password" name="password" id="password" dir="ltr" required autocomplete="new-password"
        class="form-control @error('password') is-invalid @enderror">
    <div class="form-text">{{ __('auth.password_rules') }}</div>
    @error('password')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="mb-4">
    <label class="form-label" for="password_confirmation">{{ $confirmationLabel }}</label>
    <input type="password" name="password_confirmation" id="password_confirmation" dir="ltr" required autocomplete="new-password"
        class="form-control">
</div>
