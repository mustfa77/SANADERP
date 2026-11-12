{{-- Read-only CV (worker profile tab). Needs: $worker, $photoUrl, $fullPhotoUrl. --}}
@php
    $template = $worker->cvTemplate();
    $skills = $worker->cv_skills ?? [];
    $details = $worker->cv_details ?? [];
    $rows = [
        'cv_reference_no' => $worker->cv_reference_no,
        'cv_category' => $worker->cv_category?->label(),
        'position' => $worker->profession?->trans('name'),
        'salary' => number_format((float) $worker->salary, 0).' '.__('dashboard.sar'),
        'name' => $worker->name_en,
        'home_city' => $worker->home_city,
        'nationality' => $worker->country?->trans('nationality'),
        'gender' => $worker->gender?->label(),
        'age' => $worker->birth_date ? __('cv.years_old', ['count' => $worker->birth_date->age]) : null,
        'birth_date' => $worker->birth_date?->format('Y-m-d'),
        'education' => $worker->education,
        'religion' => $worker->religion?->label(),
        'marital_status' => $worker->marital_status?->label(),
        'children_count' => $worker->children_count,
        'height_cm' => $worker->height_cm ? $worker->height_cm.' cm' : null,
        'weight_kg' => $worker->weight_kg ? $worker->weight_kg.' kg' : null,
        'arabic_level' => $worker->arabic_level?->label(),
        'english_level' => $worker->english_level?->label(),
    ];
@endphp

<div class="row">
    <div class="col-lg-3 mb-3 text-center">
        @foreach ([$photoUrl, $fullPhotoUrl] as $url)
            @if ($url)
                <img src="{{ $url }}" alt="" class="img-fluid rounded border mb-2" style="max-height: 260px;">
            @endif
        @endforeach
        @if (! $photoUrl && ! $fullPhotoUrl)
            <div class="text-muted border rounded py-5">{{ __('cv.no_photos') }}</div>
        @endif
    </div>
    <div class="col-lg-9">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm table-borderless mb-3">
                    <tbody>
                        @foreach (array_slice($rows, 0, 9, true) as $field => $value)
                            <tr>
                                <th class="text-muted fw-normal" style="width: 45%;">{{ __('cv.fields.'.$field) }}</th>
                                <td>{{ $value ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless mb-3">
                    <tbody>
                        @foreach (array_slice($rows, 9, null, true) as $field => $value)
                            <tr>
                                <th class="text-muted fw-normal" style="width: 45%;">{{ __('cv.fields.'.$field) }}</th>
                                <td>{{ $value ?? '—' }}</td>
                            </tr>
                        @endforeach
                        @foreach ($template['fields'] as $key => $field)
                            <tr>
                                <th class="text-muted fw-normal">{{ __('cv.details.'.$key.'.label') }}</th>
                                <td>
                                    @php
                                        $detail = $details[$key] ?? null;
                                    @endphp
                                    {{ $detail === null || $detail === '' ? '—' : ($field['type'] === 'select' ? __('cv.details.'.$key.'.options.'.$detail) : $detail) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <h5 class="font-size-15">{{ __('cv.sections.experience') }}</h5>
        @if ($template['experience'] === 'detailed')
            @forelse ($worker->experiences as $experience)
                <div class="border rounded p-2 mb-2">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <strong>{{ $experience->position }}</strong>
                        <span class="text-muted font-size-13" dir="ltr">{{ $experience->period() }}</span>
                    </div>
                    <div class="text-muted font-size-13">{{ collect([$experience->company, $experience->location])->filter()->implode(' · ') }}</div>
                    @if ($experience->description)
                        <p class="mb-0 mt-1" style="white-space: pre-line;">{{ $experience->description }}</p>
                    @endif
                </div>
            @empty
                <p class="text-muted">{{ __('cv.experience.none') }}</p>
            @endforelse
        @else
            <table class="table table-sm table-bordered mb-3">
                <thead class="table-light">
                    <tr><th>{{ __('cv.experience.'.$template['place']) }}</th><th>{{ __('cv.experience.position') }}</th><th>{{ __('cv.experience.duration') }}</th></tr>
                </thead>
                <tbody>
                    @forelse ($worker->experiences as $experience)
                        <tr><td>{{ $experience->country }}</td><td>{{ $experience->position }}</td><td>{{ $experience->duration }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted">{{ __('cv.experience.none') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif

        @if ($template['skills'] !== [])
            <h5 class="font-size-15">{{ __('cv.sections.skills') }}</h5>
            <div class="row g-2">
                @foreach ($template['skills'] as $skill)
                    <div class="col-6 col-md-4">
                        <div class="d-flex align-items-center gap-2 border rounded p-2">
                            <i class="{{ in_array($skill, $skills, true) ? 'ri-checkbox-circle-fill text-success' : 'ri-close-circle-fill text-danger' }} font-size-18"></i>
                            <span>{{ __('cv.skills.'.$skill) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($worker->cv_updated_at)
            <p class="text-muted font-size-12 mt-3 mb-0">{{ __('cv.updated_at', ['date' => $worker->cv_updated_at->format('Y-m-d H:i')]) }}</p>
        @endif
    </div>
</div>
