{{-- Before / after table for an audit entry ($log). Lists are shown as added / removed. --}}
@php
    $old = $log->old_values ?? [];
    $new = $log->new_values ?? [];
    $fields = array_values(array_unique([...array_keys($old), ...array_keys($new)]));
    $format = fn (mixed $value): string => match (true) {
        is_bool($value) => $value ? __('common.yes') : __('common.no'),
        is_array($value) => implode('، ', $value) ?: '—',
        $value === null || $value === '' => '—',
        default => (string) $value,
    };
@endphp

@if ($fields !== [])
    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('audit.fields.field') }}</th>
                    <th>{{ __('audit.fields.old_values') }}</th>
                    <th>{{ __('audit.fields.new_values') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($fields as $field)
                    @php
                        $before = $old[$field] ?? null;
                        $after = $new[$field] ?? null;
                    @endphp
                    <tr>
                        <td class="text-nowrap">{{ trans()->has('validation.attributes.'.$field) ? __('validation.attributes.'.$field) : $field }}</td>
                        @if (is_array($before) || is_array($after))
                            @php
                                $removed = array_diff((array) $before, (array) $after);
                                $added = array_diff((array) $after, (array) $before);
                            @endphp
                            <td dir="ltr" class="text-start">
                                @forelse ($removed as $item)
                                    <span class="badge bg-danger-subtle text-danger me-1 mb-1">− {{ $item }}</span>
                                @empty
                                    <span class="text-muted">{{ $format($before) }}</span>
                                @endforelse
                            </td>
                            <td dir="ltr" class="text-start">
                                @forelse ($added as $item)
                                    <span class="badge bg-success-subtle text-success me-1 mb-1">+ {{ $item }}</span>
                                @empty
                                    <span class="text-muted">{{ $format($after) }}</span>
                                @endforelse
                            </td>
                        @else
                            <td>{{ $format($before) }}</td>
                            <td>{{ $format($after) }}</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
