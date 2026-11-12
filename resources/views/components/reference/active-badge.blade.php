@props(['active'])
<span {{ $attributes->merge(['class' => 'badge font-size-12 '.($active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary')]) }}>
    {{ $active ? __('common.active') : __('common.inactive') }}
</span>
