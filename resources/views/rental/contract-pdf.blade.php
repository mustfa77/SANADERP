{{-- Monthly rental contract, rendered by mPDF (RentalContractController@pdf). Labels are bilingual. --}}
@php
    $color = $site->primaryColor();
    $worker = $contract->worker;
    $client = $contract->client;
    $money = fn ($amount): string => number_format((float) $amount, 2).' SAR';
    $sections = [
        'client' => [
            'client_name' => $client?->trans('name', 'ar').($client?->name_en ? ' / '.$client->name_en : ''),
            'national_id' => $client?->national_id,
            'phone' => $client?->phone,
            'address' => trim(($client?->city ?? '').' '.($client?->district ?? '')),
        ],
        'worker' => [
            'worker_name' => $worker?->name_en.($worker?->name_ar ? ' / '.$worker->name_ar : ''),
            'worker_number' => $worker?->number(),
            'nationality' => $worker?->country?->trans('nationality', 'en'),
            'profession' => $worker?->profession?->trans('name', 'en'),
        ],
        'contract' => [
            'number' => $contract->number,
            'starts_at' => $contract->starts_at->format('Y-m-d'),
            'ends_at' => $contract->ends_at->format('Y-m-d'),
            'price' => $money($contract->price),
            'discount' => $money($contract->discount),
            'vat_amount' => $money($contract->vat_amount).' ('.(float) $contract->vat_percent.'%)',
            'total' => $money($contract->total),
        ],
    ];
@endphp
<style>
    body { font-family: dejavusans; font-size: 9.5pt; color: #333; }
    .bar { background: {{ $color }}; color: #fff; font-weight: bold; padding: 1.6mm 2mm; }
    .label { color: #555; font-size: 8.5pt; font-weight: bold; }
    .ar { text-align: right; font-size: 9pt; color: #555; }
    td { padding: 1.4mm 2mm; }
    .grid td { border-bottom: 0.2mm solid #e5e5e5; }
    .sign td { border: 0.3mm solid #ccc; height: 22mm; vertical-align: top; }
</style>

<table width="100%" style="border-bottom: 0.5mm solid #ccc; margin-bottom: 3mm;">
    <tr>
        <td width="30%">@if ($logo)<img src="{{ $logo }}" style="height: 14mm;">@endif</td>
        <td width="70%" style="text-align: right;">
            <div style="font-size: 15pt; font-weight: bold; color: {{ $color }};">{{ $site->brand('en') }}</div>
            <div style="font-size: 11pt; color: #555;">{{ $site->brand('ar') }} — {{ $contract->branch?->trans('name', 'ar') }}</div>
        </td>
    </tr>
</table>

<div style="text-align: center; margin-bottom: 3mm;">
    <div style="font-size: 17pt; font-weight: bold; color: {{ $color }};">{{ __('rental.pdf.title', [], 'ar') }}</div>
    <div style="font-size: 11pt; color: #777;">{{ __('rental.pdf.title', [], 'en') }}</div>
</div>

@foreach ($sections as $section => $fields)
    <div class="bar">{{ __('rental.pdf.sections.'.$section, [], 'en') }} &nbsp; {{ __('rental.pdf.sections.'.$section, [], 'ar') }}</div>
    <table width="100%" class="grid" style="border-collapse: collapse; margin-bottom: 3mm;">
        @foreach ($fields as $key => $value)
            <tr>
                <td class="label" width="32%">{{ strtoupper(__('rental.pdf.fields.'.$key, [], 'en')) }}</td>
                <td width="38%">{{ filled($value) ? $value : '—' }}</td>
                <td class="ar" width="30%">{{ __('rental.pdf.fields.'.$key, [], 'ar') }}</td>
            </tr>
        @endforeach
    </table>
@endforeach

<div class="bar">{{ __('rental.pdf.sections.terms', [], 'en') }} &nbsp; {{ __('rental.pdf.sections.terms', [], 'ar') }}</div>
<table width="100%" style="margin-bottom: 3mm; font-size: 8.5pt;">
    @foreach (__('rental.pdf.terms', [], 'ar') as $index => $term)
        <tr>
            <td width="50%" style="vertical-align: top;">{{ $index + 1 }}. {{ __('rental.pdf.terms', [], 'en')[$index] ?? '' }}</td>
            <td width="50%" class="ar" style="vertical-align: top;">{{ $index + 1 }}. {{ $term }}</td>
        </tr>
    @endforeach
</table>

<table width="100%" class="sign" style="border-collapse: collapse;">
    <tr>
        @foreach (['client', 'employee'] as $signer)
            <td width="50%">
                <div class="label">{{ __('rental.pdf.signatures.'.$signer, [], 'en') }}</div>
                <div class="ar">{{ __('rental.pdf.signatures.'.$signer, [], 'ar') }}</div>
                @if ($signer === 'employee' && $contract->creator)
                    <div style="margin-top: 2mm;">{{ $contract->creator->displayName() }}</div>
                @endif
            </td>
        @endforeach
    </tr>
</table>

<div style="border-top: 0.5mm solid {{ $color }}; margin-top: 4mm; padding-top: 1.5mm; font-size: 7.5pt; color: #777;">
    © {{ now()->year }} {{ $site->brand('en') }} — {{ $contract->number }}
</div>
