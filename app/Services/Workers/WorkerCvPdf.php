<?php

namespace App\Services\Workers;

use App\Models\Worker;
use App\Services\SiteSettings;
use App\Support\PdfDocument;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * Printable candidate CV (one A4 page, bilingual labels) with the face and
 * full-body photos and the CV template's skills checklist.
 */
class WorkerCvPdf
{
    public function render(Worker $worker): string
    {
        $worker->loadMissing(['country', 'profession', 'experiences']);

        return PdfDocument::render('workers.cv-pdf', [
            'worker' => $worker,
            'template' => $worker->cvTemplate(),
            'photo' => $this->path($worker->photo_path),
            'fullPhoto' => $this->path($worker->full_photo_path),
        ], $worker->name_en);
    }

    /**
     * The CV as a file download (not opened in the browser), e.g.
     * "CV-W-0004-faith-njeri.pdf".
     */
    public function download(Worker $worker): Response
    {
        $name = 'CV-'.$worker->number().(filled($worker->name_en) ? '-'.Str::slug($worker->name_en) : '').'.pdf';

        return response($this->render($worker), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $name, 'CV-'.$worker->number().'.pdf'),
        ]);
    }

    /**
     * The CV as a web page (same layout as the PDF) with "Print" and
     * "Download PDF" buttons. Photos are served by the caller's routes.
     *
     * @param  array{photo: ?string, full: ?string, download: string, back?: ?string}  $urls
     */
    public function preview(Worker $worker, array $urls): View
    {
        $worker->loadMissing(['country', 'profession', 'experiences']);

        // Rendered with the browser fonts instead of mPDF's.
        return view('workers.cv-preview', [
            'worker' => $worker,
            'template' => $worker->cvTemplate(),
            'photo' => $worker->photo_path ? $urls['photo'] : null,
            'fullPhoto' => $worker->full_photo_path ? $urls['full'] : null,
            'logo' => app(SiteSettings::class)->imageUrl('logo_dark'),
            'downloadUrl' => $urls['download'],
            'backUrl' => $urls['back'] ?? null,
        ]);
    }

    private function path(?string $stored): ?string
    {
        return $stored && Storage::disk('local')->exists($stored) ? Storage::disk('local')->path($stored) : null;
    }
}
