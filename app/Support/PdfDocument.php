<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/**
 * Shared mPDF setup for the printable A4 papers (CV, handover report):
 * Arabic shaping, DejaVu font and the site logo.
 */
class PdfDocument
{
    /**
     * Render a Blade view to a PDF string.
     *
     * @param  array<string, mixed>  $data
     */
    public static function render(string $view, array $data, string $title): string
    {
        $tempDir = storage_path('app/mpdf-tmp');
        File::ensureDirectoryExists($tempDir);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => $tempDir,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'default_font' => 'dejavusans',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 8,
            'margin_bottom' => 8,
        ]);
        $mpdf->SetTitle($title);
        $mpdf->WriteHTML(view($view, ['logo' => self::logoPath(), ...$data])->render());

        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    /**
     * The uploaded "logo for light backgrounds", or the bundled one.
     */
    public static function logoPath(): ?string
    {
        $uploaded = Setting::get('logo_dark');

        if ($uploaded && Storage::disk('public')->exists($uploaded)) {
            return Storage::disk('public')->path($uploaded);
        }

        $default = public_path('assets/images/logo-dark.png');

        return is_file($default) ? $default : null;
    }
}
