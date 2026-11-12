{{-- Handover report, rendered by mPDF (App\Services\Recruitment\RecruitmentHandoverPdf). Labels are bilingual. --}}
@php
    $color = $site->primaryColor();
    $worker = $order->worker;
    $client = $order->client;
    $money = fn (float $amount): string => number_format($amount, 2).' SAR';
    $rows = [
        'client' => [
            'client_name' => $client?->trans('name', 'ar').' / '.$client?->name_en,
            'national_id' => $client?->national_id,
            'phone' => $client?->phone,
            'city' => $client?->city,
        ],
        'worker' => [
            'worker_name' => $worker?->name_en.($worker?->name_ar ? ' / '.$worker->name_ar : ''),
            'worker_number' => $worker?->number(),
            'nationality' => $worker?->country?->trans('nationality', 'en'),
            'profession' => $order->profession?->trans('name', 'en'),
            'passport_no' => $worker?->passport_no,
        ],
        'order' => [
            'number' => $order->number,
            'visa_number' => $order->visa_number,
            'musaned_contract_no' => $order->musaned_contract_no,
            'arrived_at' => $order->arrived_at?->format('Y-m-d'),
            'camp' => $order->arrivalCamp?->trans('name'),
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
            <div style="font-size: 11pt; color: #555;">{{ $site->brand('ar') }} — {{ $order->branch?->trans('name', 'ar') }}</div>
        </td>
    </tr>
</table>

<div style="text-align: center; margin-bottom: 3mm;">
    <div style="font-size: 17pt; font-weight: bold; color: {{ $color }};">{{ __('recruitment.handover.title', [], 'ar') }}</div>
    <div style="font-size: 11pt; color: #777;">{{ __('recruitment.handover.title', [], 'en') }}</div>
    @unless ($delivered)
        <div style="margin-top: 1mm; color: #c0392b; font-size: 8.5pt;">{{ __('recruitment.handover.draft', [], 'ar') }} — {{ __('recruitment.handover.draft', [], 'en') }}</div>
    @endunless
</div>

<table width="100%" class="grid" style="border-collapse: collapse; margin-bottom: 3mm;">
    <tr>
        <td class="label" width="32%">{{ strtoupper(__('recruitment.handover.date', [], 'en')) }}</td>
        <td width="38%" dir="ltr">{{ ($order->delivered_at ?? today())->format('Y-m-d') }}</td>
        <td class="ar" width="30%">{{ __('recruitment.handover.date', [], 'ar') }}</td>
    </tr>
    <tr>
        <td class="label">{{ strtoupper(__('recruitment.handover.method', [], 'en')) }}</td>
        <td>{{ __('recruitment.delivery_methods.'.$method->value, [], 'en') }} / {{ __('recruitment.delivery_methods.'.$method->value, [], 'ar') }}</td>
        <td class="ar">{{ __('recruitment.handover.method', [], 'ar') }}</td>
    </tr>
</table>

@foreach ($rows as $section => $fields)
    <div class="bar">{{ __('recruitment.handover.sections.'.$section, [], 'en') }} &nbsp; {{ __('recruitment.handover.sections.'.$section, [], 'ar') }}</div>
    <table width="100%" class="grid" style="border-collapse: collapse; margin-bottom: 3mm;">
        @foreach ($fields as $key => $value)
            <tr>
                <td class="label" width="32%">{{ strtoupper(__('recruitment.handover.fields.'.$key, [], 'en')) }}</td>
                <td width="38%">{{ filled($value) ? $value : '—' }}</td>
                <td class="ar" width="30%">{{ __('recruitment.handover.fields.'.$key, [], 'ar') }}</td>
            </tr>
        @endforeach
    </table>
@endforeach

<div class="bar">{{ __('recruitment.handover.sections.fees', [], 'en') }} &nbsp; {{ __('recruitment.handover.sections.fees', [], 'ar') }}</div>
<table width="100%" class="grid" style="border-collapse: collapse; margin-bottom: 3mm;">
    <tr>
        <td class="label" width="32%">{{ strtoupper(__('recruitment.handover.fields.camp_days', [], 'en')) }}</td>
        <td width="38%">{{ $fees['camp_days'] }}</td>
        <td class="ar" width="30%">{{ __('recruitment.handover.fields.camp_days', [], 'ar') }}</td>
    </tr>
    <tr>
        <td class="label">{{ strtoupper(__('recruitment.fields.camp_fee', [], 'en')) }}</td>
        <td>{{ $money($fees['camp_fee']) }}</td>
        <td class="ar">{{ __('recruitment.fields.camp_fee', [], 'ar') }}</td>
    </tr>
    <tr>
        <td class="label">{{ strtoupper(__('recruitment.fields.delivery_fee', [], 'en')) }}</td>
        <td>{{ $money($fees['delivery_fee']) }}</td>
        <td class="ar">{{ __('recruitment.fields.delivery_fee', [], 'ar') }}</td>
    </tr>
    <tr>
        <td class="label">{{ strtoupper(__('recruitment.fields.fees_total', [], 'en')) }}</td>
        <td style="font-weight: bold;">{{ $money($fees['total']) }}</td>
        <td class="ar">{{ __('recruitment.fields.fees_total', [], 'ar') }}</td>
    </tr>
</table>

<div class="bar">{{ __('recruitment.handover.sections.receiver', [], 'en') }} &nbsp; {{ __('recruitment.handover.sections.receiver', [], 'ar') }}</div>
<table width="100%" class="grid" style="border-collapse: collapse; margin-bottom: 3mm;">
    @foreach (['receiver_name' => $order->receiver_name, 'receiver_phone' => $order->receiver_phone, 'probation_ends_at' => $order->probation_ends_at?->format('Y-m-d'), 'delivery_notes' => $order->delivery_notes] as $key => $value)
        <tr>
            <td class="label" width="32%">{{ strtoupper(__('recruitment.fields.'.$key, [], 'en')) }}</td>
            <td width="38%">{{ filled($value) ? $value : '' }}&nbsp;</td>
            <td class="ar" width="30%">{{ __('recruitment.fields.'.$key, [], 'ar') }}</td>
        </tr>
    @endforeach
</table>

<div style="font-size: 8.5pt; color: #555; margin-bottom: 3mm;">
    <div style="text-align: right;">{{ __('recruitment.handover.declaration', ['days' => \App\Models\Setting::integer('probation_days')], 'ar') }}</div>
    <div>{{ __('recruitment.handover.declaration', ['days' => \App\Models\Setting::integer('probation_days')], 'en') }}</div>
</div>

<table width="100%" class="sign" style="border-collapse: collapse;">
    <tr>
        @foreach (['receiver', 'employee', 'manager'] as $signer)
            <td width="33%">
                <div class="label">{{ __('recruitment.handover.signatures.'.$signer, [], 'en') }}</div>
                <div class="ar">{{ __('recruitment.handover.signatures.'.$signer, [], 'ar') }}</div>
                @if ($signer === 'employee' && $order->deliverer)
                    <div style="margin-top: 2mm;">{{ $order->deliverer->displayName() }}</div>
                @endif
            </td>
        @endforeach
    </tr>
</table>

<div style="border-top: 0.5mm solid {{ $color }}; margin-top: 4mm; padding-top: 1.5mm; font-size: 7.5pt; color: #777;">
    © {{ now()->year }} {{ $site->brand('en') }} — {{ $order->number }}
</div>
