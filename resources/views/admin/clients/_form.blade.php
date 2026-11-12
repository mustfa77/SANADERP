@csrf
@php
    $duplicate = null;
    if ($errors->has('national_id') && filled(old('national_id'))) {
        $found = \App\Models\Client::query()->whereNationalId(old('national_id'))->when($client->exists, fn ($q) => $q->whereKeyNot($client->id))->first();
        $duplicate = $found && auth()->user()->can('view', $found) ? $found : null;
    }
    $fields = [
        'name_ar' => ['type' => 'text', 'dir' => 'rtl', 'required' => true],
        'name_en' => ['type' => 'text', 'dir' => 'ltr'],
        'national_id' => ['type' => 'text', 'dir' => 'ltr', 'required' => true, 'maxlength' => 10],
        'phone' => ['type' => 'tel', 'dir' => 'ltr', 'required' => true, 'placeholder' => '05XXXXXXXX'],
        'phone2' => ['type' => 'tel', 'dir' => 'ltr', 'placeholder' => '05XXXXXXXX'],
        'email' => ['type' => 'email', 'dir' => 'ltr'],
        'city' => ['type' => 'text'],
        'district' => ['type' => 'text'],
    ];
@endphp

<div class="card">
    <div class="card-body">
        <div class="row">
            @foreach ($fields as $field => $options)
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="{{ $field }}">{{ __('clients.fields.'.$field) }} @if ($options['required'] ?? false)<span class="text-danger">*</span>@endif</label>
                    <input type="{{ $options['type'] }}" name="{{ $field }}" id="{{ $field }}" value="{{ old($field, $client->{$field}) }}"
                        @isset($options['dir']) dir="{{ $options['dir'] }}" @endisset
                        @isset($options['maxlength']) maxlength="{{ $options['maxlength'] }}" @endisset
                        @isset($options['placeholder']) placeholder="{{ $options['placeholder'] }}" @endisset
                        @if ($field === 'national_id') autocomplete="off" @endif
                        class="form-control @error($field) is-invalid @enderror">
                    @error($field)
                        <div class="invalid-feedback">{{ $message }}
                            @if ($field === 'national_id' && $duplicate)
                                <a href="{{ route('admin.clients.show', $duplicate) }}" target="_blank">{{ __('clients.validation.open_existing') }}</a>
                            @endif
                        </div>
                    @enderror
                </div>
            @endforeach

            <div class="col-md-6 mb-3">
                <label class="form-label" for="branch_id">{{ __('clients.fields.branch_id') }} <span class="text-danger">*</span></label>
                <select name="branch_id" id="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                    <option value="">{{ __('common.select') }}</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) old('branch_id', $client->branch_id) === (string) $branch->id)>{{ $branch->trans('name') }}</option>
                    @endforeach
                </select>
                @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="national_address">{{ __('clients.fields.national_address') }}</label>
                <textarea name="national_address" id="national_address" rows="2" maxlength="500" class="form-control @error('national_address') is-invalid @enderror">{{ old('national_address', $client->national_address) }}</textarea>
                @error('national_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12 mb-3">
                <label class="form-label" for="notes">{{ __('clients.fields.notes') }}</label>
                <textarea name="notes" id="notes" rows="3" maxlength="2000" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $client->notes) }}</textarea>
                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12 mb-3">
                <input type="hidden" name="is_active" value="0">
                <div class="form-check form-switch">
                    <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input" @checked(old('is_active', $client->is_active))>
                    <label class="form-check-label" for="is_active">{{ __('clients.fields.is_active') }}</label>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="ri-save-line align-middle me-1"></i> {{ __('common.save') }}</button>
            <a href="{{ $client->exists ? route('admin.clients.show', $client) : route('admin.clients.index') }}" class="btn btn-light">{{ __('common.cancel') }}</a>
        </div>
    </div>
</div>
