{{-- Client overview tab. --}}
<div class="row">
    <div class="col-lg-6 mb-4">
        <table class="table table-sm table-borderless mb-0">
            <tbody>
                <tr>
                    <th class="text-muted fw-normal" style="width: 45%;">{{ __('clients.fields.name_ar') }}</th>
                    <td>{{ $client->name_ar }}</td>
                </tr>
                <tr>
                    <th class="text-muted fw-normal">{{ __('clients.fields.name_en') }}</th>
                    <td>{{ $client->name_en ?: '—' }}</td>
                </tr>
                <tr>
                    <th class="text-muted fw-normal">{{ __('clients.fields.national_id') }}</th>
                    <td><x-masked :value="$client->national_id" permission="clients.view_sensitive" type="national_id" /></td>
                </tr>
                @foreach (['phone', 'phone2', 'email'] as $field)
                    <tr>
                        <th class="text-muted fw-normal">{{ __('clients.fields.'.$field) }}</th>
                        <td dir="ltr" class="text-start">{{ $client->{$field} ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="col-lg-6 mb-4">
        <table class="table table-sm table-borderless mb-0">
            <tbody>
                @foreach (['city', 'district', 'national_address'] as $field)
                    <tr>
                        <th class="text-muted fw-normal" style="width: 45%;">{{ __('clients.fields.'.$field) }}</th>
                        <td>{{ $client->{$field} ?: '—' }}</td>
                    </tr>
                @endforeach
                <tr>
                    <th class="text-muted fw-normal">{{ __('clients.fields.branch_id') }}</th>
                    <td>{{ $client->branch?->trans('name') }}</td>
                </tr>
                <tr>
                    <th class="text-muted fw-normal">{{ __('clients.fields.created_by') }}</th>
                    <td>{{ $client->creator?->displayName() ?? '—' }} · <span dir="ltr">{{ $client->created_at?->format('Y-m-d') }}</span></td>
                </tr>
            </tbody>
        </table>
    </div>
    @if ($client->notes)
        <div class="col-12">
            <h5 class="font-size-15">{{ __('clients.fields.notes') }}</h5>
            <p class="mb-0" style="white-space: pre-line;">{{ $client->notes }}</p>
        </div>
    @endif
</div>
