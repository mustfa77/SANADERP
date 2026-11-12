{{-- Candidate CV, rendered by mPDF (App\Services\Workers\WorkerCvPdf). Labels are always bilingual. --}}
@php
    $color = $site->primaryColor();
    $t = fn (string $key, array $replace = []) => ['en' => __($key, $replace, 'en'), 'ar' => __($key, $replace, 'ar')];
    $details = $worker->cv_details ?? [];
    $skills = $worker->cv_skills ?? [];
    $info = [
        'name' => $worker->name_en,
        'home_city' => $worker->home_city,
        'nationality' => $worker->country?->trans('nationality', 'en'),
        'gender' => $worker->gender ? __('workers.genders.'.$worker->gender->value, [], 'en') : null,
        'age' => $worker->birth_date ? __('cv.years_old', ['count' => $worker->birth_date->age], 'en') : null,
        'birth_date' => $worker->birth_date?->format('d-M-Y'),
        'education' => $worker->education,
        'religion' => $worker->religion ? __('workers.religions.'.$worker->religion->value, [], 'en') : null,
        'marital_status' => $worker->marital_status ? __('workers.marital_statuses.'.$worker->marital_status->value, [], 'en') : null,
        'children_count' => $worker->children_count,
        'height_cm' => $worker->height_cm ? $worker->height_cm.' cm' : null,
        'weight_kg' => $worker->weight_kg ? $worker->weight_kg.' kg' : null,
        'arabic_level' => $worker->arabic_level ? __('cv.language_levels.'.$worker->arabic_level->value, [], 'en') : null,
        'english_level' => $worker->english_level ? __('cv.language_levels.'.$worker->english_level->value, [], 'en') : null,
    ];
    foreach ($template['fields'] as $key => $field) {
        // Links (e.g. the test video) are not useful on paper.
        if ($field['type'] === 'url') {
            continue;
        }
        $value = $details[$key] ?? null;
        $info['detail:'.$key] = $value === null || $value === '' ? null : ($field['type'] === 'select' ? __('cv.details.'.$key.'.options.'.$value, [], 'en') : $value);
    }
    $skillRows = array_chunk($template['skills'], 4);
@endphp
<style>
    body { font-family: dejavusans; font-size: 9pt; color: #333; }
    .bar { background: {{ $color }}; color: #fff; font-weight: bold; text-align: center; padding: 2mm; }
    .label { color: #555; font-size: 8pt; font-weight: bold; }
    .ar { text-align: right; font-size: 8.5pt; color: #555; }
    td { padding: 1.1mm 2mm; }
    .grid td { border-bottom: 0.2mm solid #eee; }
    .skill { border: 0.3mm solid #ddd; text-align: center; padding: 2mm; background: #fafafa; }
</style>

{{-- Header --}}
<table width="100%" style="border-bottom: 0.5mm solid #ccc; margin-bottom: 3mm;">
    <tr>
        <td width="30%">@if ($logo)<img src="{{ $logo }}" style="height: 14mm;">@endif</td>
        <td width="70%" style="text-align: right;">
            <div style="font-size: 16pt; font-weight: bold; color: {{ $color }};">{{ $site->brand('en') }}</div>
            <div style="font-size: 11pt; color: #555;">{{ $site->brand('ar') }}</div>
        </td>
    </tr>
</table>

<div style="text-align: center; margin-bottom: 3mm;">
    <div style="font-size: 16pt; font-weight: bold; color: {{ $color }};">{{ __('cv.pdf.title', [], 'ar') }}</div>
    <div style="font-size: 12pt; color: #777;">{{ __('cv.pdf.title', [], 'en') }}</div>
</div>

<table width="100%">
    <tr>
        {{-- Photos --}}
        <td width="31%" style="vertical-align: top; padding: 0 3mm 0 0;">
            <div style="border: 0.6mm dashed {{ $color }}; padding: 2mm; text-align: center;">
                @if ($photo)
                    <img src="{{ $photo }}" style="width: 100%; max-height: 62mm;">
                    <div style="background: #eee; padding: 1mm; margin-bottom: 2mm;">{{ __('cv.fields.photo', [], 'ar') }}</div>
                @endif
                @if ($fullPhoto)
                    <img src="{{ $fullPhoto }}" style="width: 100%; max-height: 95mm;">
                    <div style="background: #eee; padding: 1mm;">{{ __('cv.fields.full_photo', [], 'ar') }}</div>
                @endif
                @if (! $photo && ! $fullPhoto)
                    <div style="padding: 30mm 0; color: #999;">{{ __('cv.no_photos', [], 'en') }}</div>
                @endif
            </div>
        </td>

        {{-- Details --}}
        <td width="69%" style="vertical-align: top; padding: 0;">
            <table width="100%" style="border-collapse: collapse; margin-bottom: 3mm;">
                <tr>
                    @foreach (['date' => now()->format('d-M-Y'), 'cv_reference_no' => $worker->cv_reference_no ?? $worker->number(), 'cv_category' => $worker->cv_category ? __('cv.categories.'.$worker->cv_category->value, [], 'en') : '—'] as $key => $value)
                        <td class="bar" width="33%">{{ $key === 'date' ? __('cv.pdf.date', [], 'en') : __('cv.fields.'.$key, [], 'en') }}<br>{{ $key === 'date' ? __('cv.pdf.date', [], 'ar') : __('cv.fields.'.$key, [], 'ar') }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td style="text-align: center;">{{ now()->format('d-M-Y') }}</td>
                    <td style="text-align: center;">{{ $worker->cv_reference_no ?? $worker->number() }}</td>
                    <td style="text-align: center;">{{ $worker->cv_category ? __('cv.categories.'.$worker->cv_category->value, [], 'en') : '—' }}</td>
                </tr>
            </table>
            <table width="100%" class="grid" style="border-collapse: collapse; margin-bottom: 3mm;">
                <tr><td class="label">{{ __('cv.fields.position', [], 'en') }}</td><td style="text-align: center;">{{ strtoupper((string) $worker->profession?->trans('name', 'en')) }}</td><td class="ar">{{ __('cv.fields.position', [], 'ar') }}</td></tr>
                <tr><td class="label">{{ __('cv.fields.salary', [], 'en') }}</td><td style="text-align: center;">{{ number_format((float) $worker->salary) }} SAR</td><td class="ar">{{ __('cv.fields.salary', [], 'ar') }}</td></tr>
            </table>

            <div class="bar">{{ __('cv.sections.personal', [], 'en') }} &nbsp; {{ __('cv.sections.personal', [], 'ar') }}</div>
            <table width="100%" class="grid" style="border-collapse: collapse; margin-bottom: 3mm;">
                @foreach ($info as $key => $value)
                    @php
                        $labelKey = str_starts_with($key, 'detail:') ? 'cv.details.'.substr($key, 7).'.label' : 'cv.fields.'.$key;
                    @endphp
                    <tr>
                        <td class="label" width="32%">{{ strtoupper(__($labelKey, [], 'en')) }}</td>
                        <td width="38%">{{ $value ?? 'N/A' }}</td>
                        <td class="ar" width="30%">{{ __($labelKey, [], 'ar') }}</td>
                    </tr>
                @endforeach
            </table>

            <div class="bar">{{ __('cv.sections.experience', [], 'en') }} &nbsp; {{ __('cv.sections.experience', [], 'ar') }}</div>
            @if ($template['experience'] === 'detailed')
                @forelse ($worker->experiences as $experience)
                    <table width="100%" class="grid" style="border-collapse: collapse; margin-bottom: 2mm;">
                        @foreach ([
                            'position' => $experience->position,
                            'started_at' => $experience->period(),
                            'company' => $experience->company,
                            'location' => $experience->location,
                            'description' => $experience->description,
                        ] as $column => $value)
                            <tr>
                                <td class="label" width="28%">{{ strtoupper(__('cv.experience.'.$column, [], 'en')) }}</td>
                                <td width="47%" style="white-space: pre-line;">{{ $value ?: 'N/A' }}</td>
                                <td class="ar" width="25%">{{ __('cv.experience.'.$column, [], 'ar') }}</td>
                            </tr>
                        @endforeach
                    </table>
                @empty
                    <div style="text-align: center; padding: 2mm;">N/A</div>
                @endforelse
            @else
                <table width="100%" class="grid" style="border-collapse: collapse;">
                    <tr>
                        @foreach ([$template['place'], 'position', 'duration'] as $column)
                            <td class="label" style="text-align: center;">{{ strtoupper(__('cv.experience.'.$column, [], 'en')) }}<br>{{ __('cv.experience.'.$column, [], 'ar') }}</td>
                        @endforeach
                    </tr>
                    @forelse ($worker->experiences as $experience)
                        <tr>
                            <td style="text-align: center;">{{ $experience->country }}</td>
                            <td style="text-align: center;">{{ $experience->position }}</td>
                            <td style="text-align: center;">{{ $experience->duration }}</td>
                        </tr>
                    @empty
                        <tr><td style="text-align: center;">N/A</td><td style="text-align: center;">N/A</td><td style="text-align: center;">N/A</td></tr>
                    @endforelse
                </table>
            @endif
        </td>
    </tr>
</table>

{{-- Skills (some templates have none) --}}
@if ($skillRows !== [])
<table width="100%" style="border-collapse: separate; border-spacing: 1.5mm; margin-top: 4mm;">
    @foreach ($skillRows as $row)
        <tr>
            @foreach ($row as $skill)
                <td class="skill" width="25%">
                    <div style="font-size: 9pt;">{{ __('cv.skills.'.$skill, [], 'ar') }}</div>
                    <div style="font-size: 8pt; color: #666;">{{ __('cv.skills.'.$skill, [], 'en') }}</div>
                    <div style="font-size: 16pt; margin-top: 1mm; color: {{ in_array($skill, $skills, true) ? '#1e3a8a' : '#d32f2f' }};">{{ in_array($skill, $skills, true) ? '✔' : '✘' }}</div>
                </td>
            @endforeach
            @for ($i = count($row); $i < 4; $i++)
                <td width="25%"></td>
            @endfor
        </tr>
    @endforeach
</table>
@endif

<div style="border-top: 0.5mm solid {{ $color }}; margin-top: 4mm; padding-top: 1.5mm; font-size: 7.5pt; color: #777;">
    © {{ now()->year }} {{ $site->brand('en') }}
</div>
