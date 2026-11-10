@csrf

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="name_ar">{{ __('roles.fields.name_ar') }} <span class="text-danger">*</span></label>
                <input type="text" name="name_ar" id="name_ar" dir="rtl" value="{{ old('name_ar', $role->name_ar) }}"
                    class="form-control @error('name_ar') is-invalid @enderror" required>
                @error('name_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="name_en">{{ __('roles.fields.name_en') }} <span class="text-danger">*</span></label>
                <input type="text" name="name_en" id="name_en" dir="ltr" value="{{ old('name_en', $role->name_en) }}"
                    class="form-control @error('name_en') is-invalid @enderror" required>
                @error('name_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="name">{{ __('roles.fields.name') }} <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" dir="ltr" value="{{ old('name', $role->name) }}"
                    class="form-control @error('name') is-invalid @enderror" placeholder="rental_team"
                    @disabled($role->is_system) @required(! $role->is_system)>
                <div class="form-text">{{ __('roles.key_hint') }}</div>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3 d-flex align-items-center">
                <div class="form-check form-switch mt-md-3">
                    <input type="hidden" name="requires_2fa" value="0">
                    <input type="checkbox" name="requires_2fa" value="1" id="requires_2fa" class="form-check-input" @checked(old('requires_2fa', $role->requires_2fa))>
                    <label class="form-check-label" for="requires_2fa">{{ __('roles.fields.requires_2fa') }}</label>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="description_ar">{{ __('roles.fields.description_ar') }}</label>
                <textarea name="description_ar" id="description_ar" dir="rtl" rows="3"
                    class="form-control @error('description_ar') is-invalid @enderror">{{ old('description_ar', $role->description_ar) }}</textarea>
                @error('description_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="description_en">{{ __('roles.fields.description_en') }}</label>
                <textarea name="description_en" id="description_en" dir="ltr" rows="3"
                    class="form-control @error('description_en') is-invalid @enderror">{{ old('description_en', $role->description_en) }}</textarea>
                @error('description_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary waves-effect waves-light"><i class="ri-save-line align-middle me-1"></i> {{ __('common.save') }}</button>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-light">{{ __('common.cancel') }}</a>
        </div>
    </div>
</div>
