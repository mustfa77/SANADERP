{{-- Worker status history tab: timeline of every status change, newest first. --}}
@if ($worker->statusHistory->isEmpty())
    <p class="text-muted text-center py-4 mb-0">{{ __('workers.no_status_history') }}</p>
@else
    <ul class="list-unstyled mb-0">
        @foreach ($worker->statusHistory as $entry)
            <li class="d-flex pb-4 {{ $loop->last ? '' : 'border-start border-2' }} ps-3 position-relative" style="border-color: var(--bs-border-color) !important;">
                <span class="position-absolute top-0 start-0 translate-middle rounded-circle bg-{{ $entry->to_status->color() }}" style="width: 12px; height: 12px;"></span>
                <div class="flex-grow-1">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @if ($entry->from_status)
                            <x-worker-status :status="$entry->from_status" />
                            <i class="{{ app()->getLocale() === 'ar' ? 'ri-arrow-left-line' : 'ri-arrow-right-line' }} text-muted"></i>
                        @else
                            <span class="badge bg-light text-body">{{ __('workers.initial_status') }}</span>
                        @endif
                        <x-worker-status :status="$entry->to_status" />
                    </div>
                    @if ($entry->reason)
                        <p class="mb-1 mt-2">{{ $entry->reason }}</p>
                    @endif
                    <small class="text-muted">
                        <span dir="ltr">{{ $entry->changed_at->format('Y-m-d H:i') }}</span>
                        · {{ $entry->changer?->displayName() ?? __('audit.system') }}
                    </small>
                </div>
            </li>
        @endforeach
    </ul>
@endif
