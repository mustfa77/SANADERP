{{-- Reason, housing and note of a return ($prefix keeps the ids unique; $reasonRequired). --}}
<div class="col-md-4">
    <label class="form-label" for="{{ $prefix }}_reason">{{ __('rental.fields.close_reason_id') }}</label>
    <select name="close_reason_id" id="{{ $prefix }}_reason" @required($reasonRequired) class="form-select @error('close_reason_id') is-invalid @enderror">
        <option value="">{{ $reasonRequired ? __('common.select') : __('rental.no_reason') }}</option>
        @foreach ($closeReasons as $reason)
            <option value="{{ $reason->id }}" @selected((string) old('close_reason_id') === (string) $reason->id)>{{ $reason->trans('name') }}</option>
        @endforeach
    </select>
    @unless ($reasonRequired)
        <div class="form-text">{{ __('rental.hints.reason') }}</div>
    @endunless
    @error('close_reason_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="col-md-4">
    <label class="form-label" for="{{ $prefix }}_camp">{{ __('rental.fields.camp_id') }}</label>
    <select name="camp_id" id="{{ $prefix }}_camp" class="form-select">
        <option value="">{{ __('rental.no_camp') }}</option>
        @foreach ($camps as $camp)
            <option value="{{ $camp->id }}" @selected((string) old('camp_id', $loop->first ? $camp->id : '') === (string) $camp->id)>{{ $camp->trans('name') }}</option>
        @endforeach
    </select>
</div>
<div class="col-12">
    <label class="form-label" for="{{ $prefix }}_note">{{ __('rental.fields.close_note') }}</label>
    <input type="text" name="close_note" id="{{ $prefix }}_note" maxlength="1000" value="{{ old('close_note') }}" class="form-control">
</div>
