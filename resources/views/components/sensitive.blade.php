@props(['value', 'permission', 'type'])
{{--
    Shows a sensitive value, masked unless the user has the given permission.
    <x-sensitive :value="$worker->passport_number" permission="workers.view_sensitive" type="passport" />
--}}
<span dir="ltr" {{ $attributes }}>{{ app(\App\Services\SensitiveData::class)->display($value, $permission, $type) ?? '—' }}</span>
