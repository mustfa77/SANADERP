@props(['status'])
{{-- Badge for an App\Enums\RecruitmentStatus. --}}
<span {{ $attributes->merge(['class' => 'badge font-size-12 bg-'.$status->color().'-subtle text-'.$status->color()]) }}>
    {{ $status->label() }}
</span>
