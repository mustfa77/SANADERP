@csrf
@php
    $user = auth()->user();
    $enumValue = fn ($value) => $value instanceof \BackedEnum ? $value->value : $value;
    $dateValue = fn (string $field) => old($field, $worker->{$field}?->format('Y-m-d'));
    $selected = fn (string $field) => (string) old($field, $enumValue($worker->{$field}));
    $duplicates = [];
    foreach (['passport' => 'passport_no', 'iqama' => 'iqama_no'] as $type => $field) {
        if ($errors->has($field) && filled(old($field))) {
            $found = \App\Models\Worker::query()->whereIdentifier($type, old($field))->when($worker->exists, fn ($q) => $q->whereKeyNot($worker->id))->first();
            $duplicates[$field] = $found && $user->can('view', $found) ? $found : null;
        }
    }
@endphp

<div class="card">
    <div class="card-body">
        {{-- Identity --}}
        <h5 class="font-size-15 mb-3"><i class="ri-user-line me-1"></i>{{ __('workers.sections.identity') }}</h5>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="name_en">{{ __('workers.fields.name_en') }} <span class="text-danger">*</span></label>
                <input type="text" name="name_en" id="name_en" dir="ltr" value="{{ old('name_en', $worker->name_en) }}" class="form-control @error('name_en') is-invalid @enderror">
                <div class="form-text">{{ __('workers.hints.name_en') }}</div>
                @error('name_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="name_ar">{{ __('workers.fields.name_ar') }}</label>
                <input type="text" name="name_ar" id="name_ar" dir="rtl" value="{{ old('name_ar', $worker->name_ar) }}" class="form-control @error('name_ar') is-invalid @enderror">
                @error('name_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            @foreach (['gender' => \App\Enums\Gender::cases(), 'religion' => \App\Enums\Religion::cases(), 'marital_status' => \App\Enums\MaritalStatus::cases()] as $field => $cases)
                <div class="col-md-3 mb-3">
                    <label class="form-label" for="{{ $field }}">{{ __('workers.fields.'.$field) }} @if ($field === 'gender')<span class="text-danger">*</span>@endif</label>
                    <select name="{{ $field }}" id="{{ $field }}" class="form-select @error($field) is-invalid @enderror">
                        <option value="">{{ __('common.select') }}</option>
                        @foreach ($cases as $case)
                            <option value="{{ $case->value }}" @selected($selected($field) === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                    @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            @endforeach
            <div class="col-md-3 mb-3">
                <label class="form-label" for="birth_date">{{ __('workers.fields.birth_date') }}</label>
                <input type="date" name="birth_date" id="birth_date" value="{{ $dateValue('birth_date') }}" class="form-control @error('birth_date') is-invalid @enderror">
                @error('birth_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="photo">{{ __('workers.fields.photo') }}</label>
                <div class="d-flex align-items-center gap-2">
                    @if ($worker->exists && $worker->photo_path)
                        @include('admin.workers._avatar', ['worker' => $worker, 'size' => 'md'])
                    @endif
                    <input type="file" name="photo" id="photo" accept=".jpg,.jpeg,.png,.webp" class="form-control @error('photo') is-invalid @enderror">
                </div>
                @error('photo') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
        </div>

        {{-- Origin and location --}}
        <h5 class="font-size-15 mb-3 mt-2"><i class="ri-earth-line me-1"></i>{{ __('workers.sections.origin') }}</h5>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label" for="country_id">{{ __('workers.fields.country_id') }} <span class="text-danger">*</span></label>
                <select name="country_id" id="country_id" class="form-select @error('country_id') is-invalid @enderror">
                    <option value="">{{ __('common.select') }}</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country->id }}" @selected($selected('country_id') === (string) $country->id)>{{ $country->trans('name') }}</option>
                    @endforeach
                </select>
                @error('country_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="agency_id">{{ __('workers.fields.agency_id') }}</label>
                <select name="agency_id" id="agency_id" class="form-select @error('agency_id') is-invalid @enderror" data-filter-by="country_id">
                    <option value="">{{ __('common.none') }}</option>
                    @foreach ($agencies as $agency)
                        <option value="{{ $agency->id }}" data-parent="{{ $agency->country_id }}" @selected($selected('agency_id') === (string) $agency->id)>{{ $agency->trans('name') }}</option>
                    @endforeach
                </select>
                <div class="form-text">{{ __('workers.hints.agency') }}</div>
                @error('agency_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="profession_id">{{ __('workers.fields.profession_id') }} <span class="text-danger">*</span></label>
                <select name="profession_id" id="profession_id" class="form-select @error('profession_id') is-invalid @enderror">
                    <option value="">{{ __('common.select') }}</option>
                    @foreach ($professions as $profession)
                        <option value="{{ $profession->id }}" @selected($selected('profession_id') === (string) $profession->id)>{{ $profession->trans('name') }}</option>
                    @endforeach
                </select>
                @error('profession_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="branch_id">{{ __('workers.fields.branch_id') }} <span class="text-danger">*</span></label>
                <select name="branch_id" id="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                    <option value="">{{ __('common.select') }}</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected($selected('branch_id') === (string) $branch->id)>{{ $branch->trans('name') }}</option>
                    @endforeach
                </select>
                @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="camp_id">{{ __('workers.fields.camp_id') }}</label>
                <select name="camp_id" id="camp_id" class="form-select @error('camp_id') is-invalid @enderror" data-filter-by="branch_id">
                    <option value="">{{ __('common.none') }}</option>
                    @foreach ($camps as $camp)
                        <option value="{{ $camp->id }}" data-parent="{{ $camp->branch_id }}" @selected($selected('camp_id') === (string) $camp->id)>{{ $camp->trans('name') }}</option>
                    @endforeach
                </select>
                <div class="form-text">{{ __('workers.hints.camp') }}</div>
                @error('camp_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="musaned_cv_no">{{ __('workers.fields.musaned_cv_no') }}</label>
                <input type="text" name="musaned_cv_no" id="musaned_cv_no" dir="ltr" value="{{ old('musaned_cv_no', $worker->musaned_cv_no) }}" class="form-control @error('musaned_cv_no') is-invalid @enderror">
                @error('musaned_cv_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        {{-- Passport, residency and medical --}}
        <h5 class="font-size-15 mb-3 mt-2"><i class="ri-passport-line me-1"></i>{{ __('workers.sections.identifiers') }}</h5>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label" for="passport_no">{{ __('workers.fields.passport_no') }} <span class="text-danger">*</span></label>
                <input type="text" name="passport_no" id="passport_no" dir="ltr" autocomplete="off" value="{{ old('passport_no', $worker->passport_no) }}"
                    class="form-control text-uppercase @error('passport_no') is-invalid @enderror">
                @error('passport_no')
                    <div class="invalid-feedback">{{ $message }}
                        @if ($duplicates['passport_no'] ?? null)
                            <a href="{{ route('admin.workers.show', $duplicates['passport_no']) }}" target="_blank">{{ __('workers.validation.open_existing') }}</a>
                        @endif
                    </div>
                @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="passport_type">{{ __('workers.fields.passport_type') }} <span class="text-danger">*</span></label>
                <select name="passport_type" id="passport_type" class="form-select @error('passport_type') is-invalid @enderror">
                    @foreach (\App\Enums\PassportType::cases() as $case)
                        <option value="{{ $case->value }}" @selected($selected('passport_type') === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </select>
                @error('passport_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="passport_expiry">{{ __('workers.fields.passport_expiry') }}</label>
                <input type="date" name="passport_expiry" id="passport_expiry" value="{{ $dateValue('passport_expiry') }}" class="form-control @error('passport_expiry') is-invalid @enderror">
                @error('passport_expiry') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="iqama_no">{{ __('workers.fields.iqama_no') }}</label>
                <input type="text" name="iqama_no" id="iqama_no" dir="ltr" autocomplete="off" maxlength="10" value="{{ old('iqama_no', $worker->iqama_no) }}"
                    class="form-control @error('iqama_no') is-invalid @enderror">
                @error('iqama_no')
                    <div class="invalid-feedback">{{ $message }}
                        @if ($duplicates['iqama_no'] ?? null)
                            <a href="{{ route('admin.workers.show', $duplicates['iqama_no']) }}" target="_blank">{{ __('workers.validation.open_existing') }}</a>
                        @endif
                    </div>
                @enderror
            </div>
            @foreach (['iqama_expiry', 'medical_expiry'] as $field)
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="{{ $field }}">{{ __('workers.fields.'.$field) }}</label>
                    <input type="date" name="{{ $field }}" id="{{ $field }}" value="{{ $dateValue($field) }}" class="form-control @error($field) is-invalid @enderror">
                    @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            @endforeach
        </div>

        {{-- Work --}}
        <h5 class="font-size-15 mb-3 mt-2"><i class="ri-briefcase-line me-1"></i>{{ __('workers.sections.work') }}</h5>
        <div class="row">
            @foreach (['languages' => $languages, 'skills' => $skills] as $field => $options)
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="{{ $field }}">{{ __('workers.fields.'.$field) }}</label>
                    @php
                        $chosen = array_map('strval', (array) old($field, $worker->{$field} ?? []));
                    @endphp
                    <select name="{{ $field }}[]" id="{{ $field }}" multiple class="form-control select2 @error($field) is-invalid @enderror">
                        @foreach ($options as $option)
                            <option value="{{ $option->id }}" @selected(in_array((string) $option->id, $chosen, true))>{{ $option->trans('name') }}</option>
                        @endforeach
                    </select>
                    @error($field) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
            @endforeach
            <div class="col-md-4 mb-3">
                <label class="form-label" for="experience_years">{{ __('workers.fields.experience_years') }}</label>
                <input type="number" name="experience_years" id="experience_years" min="0" max="50" dir="ltr" value="{{ old('experience_years', $worker->experience_years) }}" class="form-control @error('experience_years') is-invalid @enderror">
                @error('experience_years') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="salary">{{ __('workers.fields.salary') }} <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="number" name="salary" id="salary" min="0" step="0.01" dir="ltr" value="{{ old('salary', $worker->salary) }}"
                        data-original="{{ $worker->exists ? (float) $worker->salary : '' }}" class="form-control @error('salary') is-invalid @enderror">
                    <span class="input-group-text">{{ __('dashboard.sar') }}</span>
                    @error('salary') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            @if ($worker->exists)
                <div class="col-md-4 mb-3 {{ $errors->has('salary_change_reason') || old('salary_change_reason') ? '' : 'd-none' }}" id="salary-reason-wrapper">
                    <label class="form-label" for="salary_change_reason">{{ __('workers.fields.salary_change_reason') }} <span class="text-danger">*</span></label>
                    <input type="text" name="salary_change_reason" id="salary_change_reason" maxlength="500" value="{{ old('salary_change_reason') }}"
                        class="form-control @error('salary_change_reason') is-invalid @enderror">
                    @error('salary_change_reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            @endif
        </div>

        {{-- Type --}}
        <h5 class="font-size-15 mb-3 mt-2"><i class="ri-shield-user-line me-1"></i>{{ __('workers.sections.type') }}</h5>
        <div class="row">
            @foreach (['sponsor_type' => \App\Enums\SponsorType::cases(), 'rental_type' => \App\Enums\WorkerRentalType::cases()] as $field => $cases)
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="{{ $field }}">{{ __('workers.fields.'.$field) }} <span class="text-danger">*</span></label>
                    <select name="{{ $field }}" id="{{ $field }}" class="form-select @error($field) is-invalid @enderror">
                        @if ($field === 'sponsor_type')
                            <option value="">{{ __('common.select') }}</option>
                        @endif
                        @foreach ($cases as $case)
                            <option value="{{ $case->value }}" @selected($selected($field) === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                    @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            @endforeach
            @if (! $worker->exists && $user->can('workers.change_status'))
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="initial_status">{{ __('workers.fields.initial_status') }}</label>
                    <select name="initial_status" id="initial_status" class="form-select @error('initial_status') is-invalid @enderror">
                        @foreach (config('workflow.initial') as $status)
                            <option value="{{ $status }}" @selected(old('initial_status', 'candidate_new') === $status)>{{ \App\Enums\WorkerStatus::from($status)->label() }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">{{ __('workers.hints.initial_status') }}</div>
                    @error('initial_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            @endif
        </div>

        <div class="d-flex gap-2 mt-2">
            <button type="submit" class="btn btn-primary"><i class="ri-save-line align-middle me-1"></i> {{ __('common.save') }}</button>
            <a href="{{ $worker->exists ? route('admin.workers.show', $worker) : route('admin.workers.index') }}" class="btn btn-light">{{ __('common.cancel') }}</a>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (function () {
            // Dependent selects: agencies follow the country, camps follow the branch.
            document.querySelectorAll('select[data-filter-by]').forEach(function (child) {
                var parent = document.getElementById(child.dataset.filterBy);
                var apply = function () {
                    child.querySelectorAll('option[data-parent]').forEach(function (option) {
                        var visible = parent.value !== '' && option.dataset.parent === parent.value;
                        option.hidden = ! visible;
                        option.disabled = ! visible;
                        if (! visible && option.selected) {
                            child.value = '';
                        }
                    });
                };
                parent.addEventListener('change', apply);
                apply();
            });

            // A salary change needs a reason.
            var salary = document.getElementById('salary');
            var wrapper = document.getElementById('salary-reason-wrapper');
            if (salary && wrapper && salary.dataset.original !== '') {
                salary.addEventListener('input', function () {
                    wrapper.classList.toggle('d-none', parseFloat(salary.value || 0) === parseFloat(salary.dataset.original));
                });
            }
        })();
    </script>
@endpush
