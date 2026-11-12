{{--
    Worker CV form, shared by staff (admin) and the external agency portal.
    Needs: $worker (with profession, experiences), $action, $cancelUrl, $photoUrl, $fullPhotoUrl.
--}}
@php
    $template = $worker->cvTemplate();
    $value = fn (string $field) => old($field, $worker->{$field} instanceof \BackedEnum ? $worker->{$field}->value : $worker->{$field});
    $checkedSkills = (array) old('cv_skills', $worker->cv_skills ?? []);
    $details = (array) old('cv_details', $worker->cv_details ?? []);
    $detailed = $template['experience'] === 'detailed';
    $experiences = old('experiences', $worker->experiences->map(fn ($experience) => [
        ...$experience->only(['country', 'position', 'duration', 'company', 'location', 'description']),
        'started_at' => $experience->started_at?->format('Y-m-d'),
        'ended_at' => $experience->ended_at?->format('Y-m-d'),
    ])->all());
    $experienceRows = max($detailed ? 2 : 3, count($experiences) + 1);
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" novalidate>
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                <div>
                    <h5 class="mb-1">{{ $worker->name_en }} <span class="text-muted font-size-13" dir="ltr">{{ $worker->number() }}</span></h5>
                    <p class="text-muted mb-0">{{ $worker->profession?->trans('name') }} · {{ __('cv.templates.'.($worker->profession?->cv_template ?? 'housemaid')) }}</p>
                </div>
            </div>

            {{-- Photos --}}
            <h5 class="font-size-15 mb-3">{{ __('cv.sections.photos') }}</h5>
            <div class="row mb-2">
                @foreach (['photo' => $photoUrl, 'full_photo' => $fullPhotoUrl] as $field => $url)
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="{{ $field }}">{{ __('cv.fields.'.$field) }}</label>
                        <div class="d-flex align-items-center gap-3">
                            @if ($url)
                                <img src="{{ $url }}" alt="" class="rounded border" style="height: 90px; width: auto;">
                            @endif
                            <input type="file" name="{{ $field }}" id="{{ $field }}" accept=".jpg,.jpeg,.png,.webp" class="form-control @error($field) is-invalid @enderror">
                        </div>
                        @error($field) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                @endforeach
            </div>

            {{-- Personal information --}}
            <h5 class="font-size-15 mb-3">{{ __('cv.sections.personal') }}</h5>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label" for="cv_reference_no">{{ __('cv.fields.cv_reference_no') }}</label>
                    <input type="text" name="cv_reference_no" id="cv_reference_no" dir="ltr" maxlength="30" value="{{ $value('cv_reference_no') }}" placeholder="AR-01" class="form-control @error('cv_reference_no') is-invalid @enderror">
                    @error('cv_reference_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                @foreach (['cv_category' => \App\Enums\CvCategory::cases(), 'arabic_level' => \App\Enums\LanguageLevel::cases(), 'english_level' => \App\Enums\LanguageLevel::cases()] as $field => $cases)
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="{{ $field }}">{{ __('cv.fields.'.$field) }}</label>
                        <select name="{{ $field }}" id="{{ $field }}" class="form-select @error($field) is-invalid @enderror">
                            <option value="">{{ __('common.select') }}</option>
                            @foreach ($cases as $case)
                                <option value="{{ $case->value }}" @selected((string) $value($field) === $case->value)>{{ $case->label() }}</option>
                            @endforeach
                        </select>
                        @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                @endforeach
                @foreach (['home_city' => 'text', 'education' => 'text', 'children_count' => 'number', 'height_cm' => 'number', 'weight_kg' => 'number'] as $field => $type)
                    <div class="col-md-{{ $type === 'number' ? 2 : 3 }} mb-3">
                        <label class="form-label" for="{{ $field }}">{{ __('cv.fields.'.$field) }}</label>
                        <input type="{{ $type }}" name="{{ $field }}" id="{{ $field }}" value="{{ $value($field) }}" @if ($type === 'number') dir="ltr" min="0" @endif
                            class="form-control @error($field) is-invalid @enderror">
                        @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                @endforeach
            </div>

            {{-- Template extra fields (e.g. driving licence) --}}
            @if ($template['fields'] !== [])
                <h5 class="font-size-15 mb-3">{{ __('cv.sections.details_'.($worker->profession?->cv_template ?? 'housemaid')) }}</h5>
                <div class="row">
                    @foreach ($template['fields'] as $key => $field)
                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="cv_details_{{ $key }}">{{ __('cv.details.'.$key.'.label') }}</label>
                            @if ($field['type'] === 'select')
                                <select name="cv_details[{{ $key }}]" id="cv_details_{{ $key }}" class="form-select @error('cv_details.'.$key) is-invalid @enderror">
                                    <option value="">{{ __('common.select') }}</option>
                                    @foreach ($field['options'] as $option)
                                        <option value="{{ $option }}" @selected(($details[$key] ?? null) === $option)>{{ __('cv.details.'.$key.'.options.'.$option) }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="{{ $field['type'] }}" name="cv_details[{{ $key }}]" id="cv_details_{{ $key }}" value="{{ $details[$key] ?? '' }}"
                                    @if ($field['type'] === 'number') dir="ltr" min="{{ $field['min'] ?? 0 }}" max="{{ $field['max'] ?? 1000 }}" @endif
                                    class="form-control @error('cv_details.'.$key) is-invalid @enderror">
                            @endif
                            @error('cv_details.'.$key) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Employment background --}}
            <h5 class="font-size-15 mb-3">{{ __('cv.sections.experience') }}</h5>
            @if ($detailed)
                {{-- Detailed experience (care giver): one block per job. --}}
                @for ($row = 0; $row < $experienceRows; $row++)
                    <div class="border rounded p-2 mb-2">
                        <div class="row g-2">
                            @foreach (['position' => 'text', 'company' => 'text', 'location' => 'text', 'started_at' => 'date', 'ended_at' => 'date'] as $column => $type)
                                <div class="{{ $type === 'date' ? 'col-md-2' : 'col-md-4' }}">
                                    <label class="form-label font-size-12 mb-1" for="experience_{{ $row }}_{{ $column }}">{{ __('cv.experience.'.$column) }}</label>
                                    <input type="{{ $type }}" name="experiences[{{ $row }}][{{ $column }}]" id="experience_{{ $row }}_{{ $column }}" value="{{ $experiences[$row][$column] ?? '' }}"
                                        @if ($type === 'text') maxlength="150" @endif
                                        class="form-control form-control-sm @error('experiences.'.$row.'.'.$column) is-invalid @enderror">
                                    @error('experiences.'.$row.'.'.$column) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @endforeach
                            <div class="col-12">
                                <label class="form-label font-size-12 mb-1" for="experience_{{ $row }}_description">{{ __('cv.experience.description') }}</label>
                                <textarea name="experiences[{{ $row }}][description]" id="experience_{{ $row }}_description" rows="2" maxlength="2000"
                                    placeholder="{{ __('cv.experience.description_hint') }}" class="form-control form-control-sm">{{ $experiences[$row]['description'] ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                @endfor
                <div class="form-text mb-3">{{ __('cv.experience.hint_detailed') }}</div>
            @else
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-borderless mb-0">
                        <thead class="text-muted">
                            <tr>
                                <th>{{ __('cv.experience.'.$template['place']) }}</th>
                                <th>{{ __('cv.experience.position') }}</th>
                                <th>{{ __('cv.experience.duration') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($row = 0; $row < $experienceRows; $row++)
                                <tr>
                                    @foreach (['country', 'position', 'duration'] as $column)
                                        <td>
                                            <input type="text" name="experiences[{{ $row }}][{{ $column }}]" value="{{ $experiences[$row][$column] ?? '' }}" maxlength="100"
                                                class="form-control form-control-sm @error('experiences.'.$row.'.'.$column) is-invalid @enderror">
                                        </td>
                                    @endforeach
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                    <div class="form-text">{{ __('cv.experience.hint') }}</div>
                </div>
            @endif

            {{-- Skills checklist (some templates have none) --}}
            @if ($template['skills'] !== [])
                <h5 class="font-size-15 mb-3">{{ __('cv.sections.skills') }}</h5>
                <div class="row g-2 mb-4">
                    @foreach ($template['skills'] as $skill)
                        <div class="col-6 col-md-4 col-lg-3">
                            <label class="d-flex align-items-center gap-2 border rounded p-2 h-100" for="skill_{{ $skill }}">
                                <input type="checkbox" class="form-check-input mt-0" name="cv_skills[]" id="skill_{{ $skill }}" value="{{ $skill }}" @checked(in_array($skill, $checkedSkills, true))>
                                <span>{{ __('cv.skills.'.$skill) }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>
            @endif

            @if (trans()->has('cv.documents_hint.'.($worker->profession?->cv_template ?? 'housemaid')))
                <div class="alert alert-info py-2"><i class="ri-information-line me-1"></i>{{ __('cv.documents_hint.'.$worker->profession->cv_template) }}</div>
            @endif

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="ri-save-line align-middle me-1"></i> {{ __('common.save') }}</button>
                <a href="{{ $cancelUrl }}" class="btn btn-light">{{ __('common.cancel') }}</a>
            </div>
        </div>
    </div>
</form>
