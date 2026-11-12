{{-- Worker photo (served by a permission-checked route) or initials. $size: xs | md | lg --}}
@if ($worker->photo_path)
    <img src="{{ route('admin.workers.photo', $worker) }}" alt="" class="avatar-{{ $size }} rounded-circle object-fit-cover">
@else
    <div class="avatar-{{ $size }}">
        <span class="avatar-title rounded-circle bg-primary-subtle text-primary {{ $size === 'xs' ? 'font-size-12' : 'font-size-20' }}">{{ mb_substr($worker->trans('name') ?? '?', 0, 1) }}</span>
    </div>
@endif
