{{-- "Latest completed visas" page of the printed authority file (rendered by mPDF). --}}
@php
    $dir = config('app.available_locales')[app()->getLocale()]['dir'] ?? 'ltr';
    $align = $dir === 'rtl' ? 'right' : 'left';
@endphp
<div dir="{{ $dir }}" style="font-size: 11pt; text-align: {{ $align }};">
    <h3 style="text-align: center;">{{ __('documents.pdf.completed_visas_title', ['count' => $count, 'country' => $country->trans('name')]) }}</h3>
    <p style="text-align: center; color: #555;">{{ $authority->trans('name') }}</p>

    <table style="width: 100%; border-collapse: collapse; margin-top: 5mm;" dir="{{ $dir }}">
        <tr>
            <th style="border: 0.2mm solid #ccc; padding: 2mm; width: 10mm;">#</th>
            <th style="border: 0.2mm solid #ccc; padding: 2mm;">{{ __('documents.pdf.visa_number') }}</th>
            <th style="border: 0.2mm solid #ccc; padding: 2mm; text-align: {{ $align }};">{{ __('documents.pdf.worker_name') }}</th>
            <th style="border: 0.2mm solid #ccc; padding: 2mm;">{{ __('documents.pdf.issued_at') }}</th>
        </tr>
        @forelse ($visas as $visa)
            <tr>
                <td style="border: 0.2mm solid #ccc; padding: 2mm; text-align: center;">{{ $loop->iteration }}</td>
                <td style="border: 0.2mm solid #ccc; padding: 2mm; text-align: center;" dir="ltr">{{ $visa['visa_number'] }}</td>
                <td style="border: 0.2mm solid #ccc; padding: 2mm;">{{ $visa['name'] }}</td>
                <td style="border: 0.2mm solid #ccc; padding: 2mm; text-align: center;" dir="ltr">{{ $visa['issued_at'] }}</td>
            </tr>
        @empty
            <tr><td colspan="4" style="border: 0.2mm solid #ccc; padding: 4mm; text-align: center;">{{ __('documents.pdf.no_completed_visas') }}</td></tr>
        @endforelse
    </table>
</div>
