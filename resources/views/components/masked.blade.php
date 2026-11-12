@props(['value', 'permission', 'type' => null])
{{--
    A sensitive identifier, masked unless the user has the permission (the
    single masking component; exports use App\Services\SensitiveData too).
    <x-masked :value="$worker->passport_no" permission="workers.view_sensitive" type="passport" />
    type: passport (P01****6C) | iqama | national_id (10******98); defaults by permission.
--}}
@php
    $type ??= str_starts_with($permission, 'clients.') ? 'national_id' : 'passport';
@endphp
<span dir="ltr" {{ $attributes->merge(['class' => 'font-monospace']) }}>{{ app(\App\Services\SensitiveData::class)->display($value, $permission, $type) ?? '—' }}</span>
