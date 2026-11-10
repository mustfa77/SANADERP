@csrf

<div class="card">
    <div class="card-body">
        <h5 class="font-size-15 mb-3">{{ __('branches.sections.basic') }}</h5>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="name_ar">{{ __('branches.fields.name_ar') }} <span class="text-danger">*</span></label>
                <input type="text" name="name_ar" id="name_ar" dir="rtl" value="{{ old('name_ar', $branch->name_ar) }}"
                    class="form-control @error('name_ar') is-invalid @enderror" required>
                @error('name_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="name_en">{{ __('branches.fields.name_en') }} <span class="text-danger">*</span></label>
                <input type="text" name="name_en" id="name_en" dir="ltr" value="{{ old('name_en', $branch->name_en) }}"
                    class="form-control @error('name_en') is-invalid @enderror" required>
                @error('name_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="code">{{ __('branches.fields.code') }} <span class="text-danger">*</span></label>
                <input type="text" name="code" id="code" dir="ltr" maxlength="20" value="{{ old('code', $branch->code) }}" placeholder="RYD"
                    class="form-control text-uppercase @error('code') is-invalid @enderror" required>
                <div class="form-text">{{ __('branches.code_hint') }}</div>
                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="manager_id">{{ __('branches.fields.manager') }}</label>
                <select name="manager_id" id="manager_id" class="form-select select2 @error('manager_id') is-invalid @enderror">
                    <option value="">{{ __('common.none') }}</option>
                    @foreach ($managers as $manager)
                        <option value="{{ $manager->id }}" @selected((string) old('manager_id', $branch->manager_id) === (string) $manager->id)>{{ $manager->displayName() }}</option>
                    @endforeach
                </select>
                @error('manager_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="col-12 mb-3">
                <input type="hidden" name="is_active" value="0">
                <div class="form-check form-switch">
                    <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input" @checked(old('is_active', $branch->is_active))>
                    <label class="form-check-label" for="is_active">{{ __('branches.fields.is_active') }}</label>
                </div>
            </div>
        </div>

        <h5 class="font-size-15 mb-3 mt-2">{{ __('branches.sections.contact') }}</h5>
        <div class="row">
            @foreach (['city_ar' => 'rtl', 'city_en' => 'ltr'] as $field => $direction)
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="{{ $field }}">{{ __('branches.fields.'.$field) }}</label>
                    <input type="text" name="{{ $field }}" id="{{ $field }}" dir="{{ $direction }}" value="{{ old($field, $branch->{$field}) }}"
                        class="form-control @error($field) is-invalid @enderror">
                    @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            @endforeach
            @foreach (['address_ar' => 'rtl', 'address_en' => 'ltr'] as $field => $direction)
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="{{ $field }}">{{ __('branches.fields.'.$field) }}</label>
                    <textarea name="{{ $field }}" id="{{ $field }}" dir="{{ $direction }}" rows="2" maxlength="500"
                        class="form-control @error($field) is-invalid @enderror">{{ old($field, $branch->{$field}) }}</textarea>
                    @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            @endforeach
            <div class="col-md-6 mb-3">
                <label class="form-label" for="phone">{{ __('branches.fields.phone') }}</label>
                <input type="tel" name="phone" id="phone" dir="ltr" value="{{ old('phone', $branch->phone) }}" placeholder="011XXXXXXX"
                    class="form-control @error('phone') is-invalid @enderror">
                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="email">{{ __('branches.fields.email') }}</label>
                <input type="email" name="email" id="email" dir="ltr" value="{{ old('email', $branch->email) }}"
                    class="form-control @error('email') is-invalid @enderror">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary waves-effect waves-light"><i class="ri-save-line align-middle me-1"></i> {{ __('common.save') }}</button>
            <a href="{{ $branch->exists ? route('admin.branches.show', $branch) : route('admin.branches.index') }}" class="btn btn-light">{{ __('common.cancel') }}</a>
        </div>
    </div>
</div>
