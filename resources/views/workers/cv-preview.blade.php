{{-- CV as a web page (same layout as the PDF, App\Services\Workers\WorkerCvPdf::preview) with print / download buttons. --}}
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('cv.preview_title', ['name' => $worker->name_en ?: $worker->trans('name')]) }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/icons.min.css') }}">
    <style>
        html, body { margin: 0; background: #e9ecef; }
        .toolbar { position: sticky; top: 0; z-index: 10; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: space-between;
            padding: 10px 16px; background: #fff; box-shadow: 0 1px 4px rgba(0, 0, 0, .12); font-family: system-ui, "Segoe UI", Tahoma, sans-serif; }
        .toolbar h1 { margin: 0; font-size: 16px; font-weight: 600; color: #333; }
        .toolbar .actions { display: flex; gap: 8px; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 6px; border: 1px solid transparent; font-size: 14px;
            text-decoration: none; cursor: pointer; font-family: inherit; }
        .btn-print { background: {{ $site->primaryColor() }}; color: #fff; }
        .btn-download { background: #fff; color: #333; border-color: #ced4da; }
        .btn-back { background: transparent; color: #555; }
        .sheet { box-sizing: border-box; width: 210mm; min-height: 297mm; margin: 16px auto; padding: 8mm; background: #fff; box-shadow: 0 2px 10px rgba(0, 0, 0, .15); }
        /* The CV layout is written left-to-right (English with Arabic labels), as in the PDF. */
        .sheet { direction: ltr; font-family: Tahoma, "Segoe UI", Arial, sans-serif; }
        .sheet img { max-width: 100%; }
        @media (max-width: 220mm) {
            .sheet { width: auto; min-height: 0; margin: 8px; }
        }
        @media print {
            @page { size: A4; margin: 0; }
            html, body { background: #fff; }
            .toolbar { display: none; }
            .sheet { width: auto; min-height: 0; margin: 0; box-shadow: none; }
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <h1>{{ $worker->trans('name') }} <span style="color: #888; font-weight: 400;" dir="ltr">{{ $worker->cv_reference_no ?? $worker->number() }}</span></h1>
        <div class="actions">
            @if ($backUrl)
                <a href="{{ $backUrl }}" class="btn btn-back"><i class="ri-arrow-go-back-line"></i>{{ __('common.back') }}</a>
            @endif
            <button type="button" class="btn btn-print" onclick="window.print()"><i class="ri-printer-line"></i>{{ __('cv.actions.print') }}</button>
            <a href="{{ $downloadUrl }}" class="btn btn-download" download><i class="ri-download-2-line"></i>{{ __('cv.actions.download') }}</a>
        </div>
    </div>

    <div class="sheet">
        @include('workers.cv-pdf')
    </div>
</body>
</html>
