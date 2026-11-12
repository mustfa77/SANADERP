@php
    /** @var \App\Models\User $user */
    $isEdit = $user->exists;
    $canAssign = auth()->user()->can('assignRoles', $isEdit ? $user : App\Models\User::class);
    $selectedRoles = old('roles', $user->exists ? $user->roles->pluck('name')->all() : []);
    $selectedBranches = array_map('intval', old('branches', $user->exists ? $user->branches->pluck('id')->all() : []));
    $allBranches = (bool) old('all_branches', $user->exists && $user->permissions->contains('name', 'branches.all'));
    $userType = old('user_type', $user->user_type?->value);
@endphp

@csrf

<div class="row">
    <div class="col-xl-8">
        {{-- Basic information --}}
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">{{ __('users.sections.basic') }}</h4>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="name_ar">{{ __('users.fields.name_ar') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name_ar" id="name_ar" dir="rtl" value="{{ old('name_ar', $user->name_ar) }}"
                            class="form-control @error('name_ar') is-invalid @enderror" required>
                        @error('name_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="name_en">{{ __('users.fields.name_en') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name_en" id="name_en" dir="ltr" value="{{ old('name_en', $user->name_en) }}"
                            class="form-control @error('name_en') is-invalid @enderror" required>
                        @error('name_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="email">{{ __('users.fields.email') }} <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="email" dir="ltr" value="{{ old('email', $user->email) }}"
                            class="form-control @error('email') is-invalid @enderror" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="phone">{{ __('users.fields.phone') }} <span class="text-danger">*</span></label>
                        <input type="tel" name="phone" id="phone" dir="ltr" value="{{ old('phone', $user->phone) }}" placeholder="05XXXXXXXX"
                            class="form-control @error('phone') is-invalid @enderror" required>
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="preferred_language">{{ __('users.fields.preferred_language') }}</label>
                        <select name="preferred_language" id="preferred_language" class="form-select @error('preferred_language') is-invalid @enderror">
                            @foreach (config('app.available_locales') as $code => $locale)
                                <option value="{{ $code }}" @selected(old('preferred_language', $user->preferred_language) === $code)>{{ $locale['name'] }}</option>
                            @endforeach
                        </select>
                        @error('preferred_language') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="avatar">{{ __('users.fields.avatar') }} <span class="text-muted font-size-12">({{ __('common.optional') }})</span></label>
                        <input type="file" name="avatar" id="avatar" accept="image/*" class="form-control @error('avatar') is-invalid @enderror">
                        @error('avatar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Account --}}
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">{{ __('users.sections.account') }}</h4>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="user_type">{{ __('users.fields.user_type') }} <span class="text-danger">*</span></label>
                        <select name="user_type" id="user_type" class="form-select @error('user_type') is-invalid @enderror">
                            @foreach ($types as $type)
                                <option value="{{ $type->value }}" @selected($userType === $type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </select>
                        @error('user_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3" id="agency-field" @style(['display: none' => $userType !== 'external_agency'])>
                        <label class="form-label" for="agency_id">{{ __('users.fields.agency') }} <span class="text-danger">*</span></label>
                        <select name="agency_id" id="agency_id" class="form-select @error('agency_id') is-invalid @enderror">
                            <option value="">{{ __('common.select') }}</option>
                            @foreach ($agencies as $agency)
                                <option value="{{ $agency->id }}" @selected((string) old('agency_id', $user->agency_id) === (string) $agency->id)>{{ $agency->trans('name') }}</option>
                            @endforeach
                        </select>
                        @error('agency_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="password">{{ __('users.fields.password') }} @unless ($isEdit)<span class="text-danger">*</span>@endunless</label>
                        <input type="password" name="password" id="password" dir="ltr" autocomplete="new-password"
                            class="form-control @error('password') is-invalid @enderror" @required(! $isEdit)>
                        <div class="form-text">{{ $isEdit ? __('users.password_hint_edit') : __('auth.password_rules') }}</div>
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="password_confirmation">{{ __('users.fields.password_confirmation') }}</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" dir="ltr" autocomplete="new-password" class="form-control">
                    </div>
                </div>

                <div class="form-check form-switch">
                    <input type="hidden" name="must_change_password" value="0">
                    <input type="checkbox" name="must_change_password" value="1" id="must_change_password" class="form-check-input"
                        @checked(old('must_change_password', $user->must_change_password))>
                    <label class="form-check-label" for="must_change_password">{{ __('users.fields.must_change_password') }}</label>
                </div>
            </div>
        </div>
    </div>

    {{-- Roles & branches --}}
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">{{ __('users.sections.access') }}</h4>

                @if ($canAssign)
                    <input type="hidden" name="access_submitted" value="1">

                    <div class="mb-3">
                        <label class="form-label" for="roles">{{ __('users.fields.roles') }}</label>
                        <select name="roles[]" id="roles" multiple class="form-control select2 @error('roles') is-invalid @enderror">
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}" @selected(in_array($role->name, $selectedRoles, true))>{{ $role->displayName() }}</option>
                            @endforeach
                        </select>
                        @error('roles') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-check form-switch mb-1">
                        <input type="hidden" name="all_branches" value="0">
                        <input type="checkbox" name="all_branches" value="1" id="all_branches" class="form-check-input" @checked($allBranches)>
                        <label class="form-check-label" for="all_branches">{{ __('users.fields.all_branches') }}</label>
                    </div>
                    <p class="text-muted font-size-12">{{ __('users.all_branches_hint') }}</p>

                    <div class="mb-3" id="branches-field" @style(['display: none' => $allBranches])>
                        <label class="form-label" for="branches">{{ __('users.fields.branches') }}</label>
                        <select name="branches[]" id="branches" multiple class="form-control select2 @error('branches') is-invalid @enderror">
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(in_array($branch->id, $selectedBranches, true))>{{ $branch->trans('name') }}</option>
                            @endforeach
                        </select>
                        @error('branches') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                @else
                    <div class="alert alert-warning mb-3">{{ __('users.own_access_locked') }}</div>
                    <p class="mb-1 text-muted">{{ __('users.fields.roles') }}</p>
                    <p>{{ $user->roles->map->displayName()->implode('، ') ?: __('users.no_roles') }}</p>
                @endif
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary waves-effect waves-light flex-fill">
                <i class="ri-save-line align-middle me-1"></i> {{ __('common.save') }}
            </button>
            <a href="{{ $isEdit ? route('admin.users.show', $user) : route('admin.users.index') }}" class="btn btn-light flex-fill">{{ __('common.cancel') }}</a>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        $('#user_type').on('change', function () {
            $('#agency-field').toggle(this.value === 'external_agency');
        });
        $('#all_branches').on('change', function () {
            $('#branches-field').toggle(! this.checked);
        });
    </script>
@endpush
