@props(['resource', 'record', 'name', 'field', 'label', 'dir' => null])
{{-- One form input of a reference-data resource (see ReferenceResource field types). --}}
@php
    $value = old($name, $resource->formValue($record, $name));
    $inputType = match ($field['type']) {
        'decimal', 'number' => 'number',
        'time' => 'time',
        'email' => 'email',
        'tel' => 'tel',
        default => 'text',
    };
@endphp
<div class="col-md-6 mb-3">
    <label class="form-label" for="{{ $name }}">
        {{ $label }} @if ($field['required'] ?? false)<span class="text-danger">*</span>@endif
    </label>

    @switch($field['type'])
        @case('textarea')
            <textarea name="{{ $name }}" id="{{ $name }}" rows="2" maxlength="1000" @if ($dir) dir="{{ $dir }}" @endif
                class="form-control @error($name) is-invalid @enderror">{{ $value }}</textarea>
            @break

        @case('select')
            <select name="{{ $name }}" id="{{ $name }}" class="form-select @error($name) is-invalid @enderror">
                <option value="">{{ __('common.select') }}</option>
                @foreach ($resource->options($name) as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                @endforeach
            </select>
            @break

        @default
            <input name="{{ $name }}" id="{{ $name }}" value="{{ $value }}" @if ($dir) dir="{{ $dir }}" @endif
                type="{{ $inputType }}"
                @if ($field['type'] === 'decimal') step="{{ $field['step'] ?? '0.01' }}" min="0" @endif
                @if ($field['type'] === 'number') step="1" min="0" @endif
                class="form-control @error($name) is-invalid @enderror">
    @endswitch

    @error($name) <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
