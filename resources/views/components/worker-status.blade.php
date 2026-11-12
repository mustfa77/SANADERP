@props(['status'])
{{-- Badge for an App\Enums\WorkerStatus. --}}
<span {{ $attributes->merge(['class' => 'badge font-size-12 bg-'.$status->color().'-subtle text-'.$status->color()]) }}>
    {{ $status->label() }}
</span>
