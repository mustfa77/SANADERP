@props(['status'])
{{-- Badge for an App\Enums\UserStatus. --}}
<span {{ $attributes->merge(['class' => 'badge bg-'.$status->color().'-subtle text-'.$status->color().' font-size-12']) }}>
    {{ $status->label() }}
</span>
