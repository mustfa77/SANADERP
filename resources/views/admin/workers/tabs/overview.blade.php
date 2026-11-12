{{-- Worker overview tab: all data and salary history. --}}
@php
    $lookupNames = fn (?array $ids): string => collect($ids ?? [])->map(fn ($id) => $lookups->get($id)?->trans('name'))->filter()->implode('، ') ?: '—';
    $date = fn ($value): string => $value?->format('Y-m-d') ?? '—';
    $sections = [
        'identity' => [
            'name_en' => $worker->name_en,
            'name_ar' => $worker->name_ar ?: '—',
            'gender' => $worker->gender?->label(),
            'birth_date' => $date($worker->birth_date),
            'religion' => $worker->religion?->label() ?? '—',
            'marital_status' => $worker->marital_status?->label() ?? '—',
        ],
        'origin' => [
            'country_id' => $worker->country?->trans('name'),
            'profession_id' => $worker->profession?->trans('name'),
            'agency_id' => $worker->agency?->trans('name') ?? '—',
            'branch_id' => $worker->branch?->trans('name'),
            'camp_id' => $worker->camp?->trans('name') ?? '—',
            'musaned_cv_no' => $worker->musaned_cv_no ?: '—',
        ],
        'identifiers' => [
            'passport_type' => $worker->passport_type?->label(),
            'passport_expiry' => $date($worker->passport_expiry),
            'iqama_expiry' => $date($worker->iqama_expiry),
            'medical_expiry' => $date($worker->medical_expiry),
        ],
        'work' => [
            'languages' => $lookupNames($worker->languages),
            'skills' => $lookupNames($worker->skills),
            'experience_years' => (string) $worker->experience_years,
            'salary' => number_format((float) $worker->salary, 2).' '.__('dashboard.sar'),
            'sponsor_type' => $worker->sponsor_type->label(),
            'rental_type' => $worker->rental_type->label(),
        ],
    ];
@endphp

<div class="row">
    @foreach ($sections as $section => $values)
        <div class="col-lg-6 mb-4">
            <h5 class="font-size-15 mb-3">{{ __('workers.sections.'.$section) }}</h5>
            <table class="table table-sm table-borderless mb-0">
                <tbody>
                    @if ($section === 'identifiers')
                        <tr>
                            <th class="text-muted fw-normal" style="width: 45%;">{{ __('workers.fields.passport_no') }}</th>
                            <td><x-masked :value="$worker->passport_no" permission="workers.view_sensitive" type="passport" /></td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-normal">{{ __('workers.fields.iqama_no') }}</th>
                            <td><x-masked :value="$worker->iqama_no" permission="workers.view_sensitive" type="iqama" /></td>
                        </tr>
                    @endif
                    @foreach ($values as $field => $value)
                        <tr>
                            <th class="text-muted fw-normal" style="width: 45%;">{{ __('workers.fields.'.$field) }}</th>
                            <td>{{ $value }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</div>

<h5 class="font-size-15 mb-3">{{ __('workers.salary_history') }}</h5>
<div class="table-responsive">
    <table class="table table-sm table-centered mb-0">
        <thead class="table-light">
            <tr>
                <th>{{ __('workers.fields.changed_at') }}</th>
                <th>{{ __('workers.fields.old_salary') }}</th>
                <th>{{ __('workers.fields.new_salary') }}</th>
                <th>{{ __('workers.fields.salary_change_reason') }}</th>
                <th>{{ __('workers.fields.changed_by') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($worker->salaryChanges as $change)
                <tr>
                    <td dir="ltr" class="text-start">{{ $change->changed_at->format('Y-m-d H:i') }}</td>
                    <td>{{ number_format((float) $change->old_salary, 2) }}</td>
                    <td class="fw-semibold">{{ number_format((float) $change->new_salary, 2) }}</td>
                    <td>{{ $change->reason }}</td>
                    <td>{{ $change->changer?->displayName() ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted">{{ __('workers.no_salary_changes') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
