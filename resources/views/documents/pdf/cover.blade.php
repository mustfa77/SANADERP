{{-- Cover page of the printed authority file (rendered by mPDF). --}}
@php
    $dir = config('app.available_locales')[app()->getLocale()]['dir'] ?? 'ltr';
    $align = $dir === 'rtl' ? 'right' : 'left';
@endphp
<div dir="{{ $dir }}" style="font-size: 12pt; text-align: {{ $align }};">
    <h2 style="text-align: center; margin-bottom: 4mm;">{{ $site->brand() }}</h2>
    <h3 style="text-align: center; margin-top: 0; color: #555;">{{ __('documents.pdf.title', ['authority' => $authority->trans('name')]) }}</h3>

    <table style="width: 100%; border-collapse: collapse; margin-top: 8mm;" dir="{{ $dir }}">
        <tr>
            <td style="width: 35%; padding: 2mm; background: #f3f4f6;">{{ __('documents.pdf.owner') }}</td>
            <td style="padding: 2mm;">{{ $ownerName }}</td>
        </tr>
        <tr>
            <td style="padding: 2mm; background: #f3f4f6;">{{ __('documents.pdf.authority') }}</td>
            <td style="padding: 2mm;">{{ $authority->trans('name') }}</td>
        </tr>
        <tr>
            <td style="padding: 2mm; background: #f3f4f6;">{{ __('documents.pdf.printed_at') }}</td>
            <td style="padding: 2mm;" dir="ltr">{{ now()->format('Y-m-d H:i') }}</td>
        </tr>
        <tr>
            <td style="padding: 2mm; background: #f3f4f6;">{{ __('documents.pdf.printed_by') }}</td>
            <td style="padding: 2mm;">{{ auth()->user()?->displayName() ?? '—' }}</td>
        </tr>
    </table>

    <h4 style="margin-top: 10mm;">{{ __('documents.pdf.contents') }}</h4>
    <table style="width: 100%; border-collapse: collapse;" dir="{{ $dir }}">
        <tr>
            <th style="border: 0.2mm solid #ccc; padding: 2mm; width: 10mm;">#</th>
            <th style="border: 0.2mm solid #ccc; padding: 2mm; text-align: {{ $align }};">{{ __('documents.fields.document') }}</th>
            <th style="border: 0.2mm solid #ccc; padding: 2mm;">{{ __('documents.fields.version') }}</th>
        </tr>
        @forelse ($items as $item)
            <tr>
                <td style="border: 0.2mm solid #ccc; padding: 2mm; text-align: center;">{{ $loop->iteration }}</td>
                <td style="border: 0.2mm solid #ccc; padding: 2mm;">{{ $item->name() }}</td>
                <td style="border: 0.2mm solid #ccc; padding: 2mm; text-align: center;">{{ $item->latestFile()->version }}</td>
            </tr>
        @empty
            <tr><td colspan="3" style="border: 0.2mm solid #ccc; padding: 2mm; text-align: center;">{{ __('documents.pdf.no_documents') }}</td></tr>
        @endforelse
    </table>
</div>
