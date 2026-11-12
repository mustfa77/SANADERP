<?php

namespace App\Services\Documents;

use App\Models\Authority;
use App\Models\CompletedVisaListSetting;
use App\Models\Country;
use App\Models\DocumentFile;
use App\Models\DocumentPrint;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use setasign\Fpdi\PdfParser\PdfParserException;
use Throwable;

/**
 * Builds the single PDF handed to an external authority: a cover page,
 * then every uploaded document flagged "include in authority file" in the
 * configured order (images one per page, PDFs page by page), then the
 * latest completed visas list when configured for the authority and country.
 */
class AuthorityFilePrintService
{
    private const MARGIN = 10;

    public function __construct(
        private DocumentChecklistService $checklist,
        private CompletedVisasProvider $completedVisas,
        private AuditLogger $audit,
    ) {}

    /**
     * Checklist rows that belong in this authority's file: flagged types
     * that are either for all authorities or for this one.
     *
     * @return Collection<int, ChecklistItem>
     */
    public function items(Model $owner, Authority $authority): Collection
    {
        return $this->checklist->required($owner)
            ->filter(fn (ChecklistItem $item): bool => $item->type->include_in_authority_file
                && ($item->type->authority_id === null || $item->type->authority_id === $authority->id))
            ->values();
    }

    /**
     * @return list<string>
     */
    public function missing(Model $owner, Authority $authority): array
    {
        return $this->items($owner, $authority)
            ->filter(fn (ChecklistItem $item): bool => $item->isRequired() && ! $item->isUploaded())
            ->map(fn (ChecklistItem $item): string => $item->name())
            ->values()
            ->all();
    }

    /**
     * @throws AuthorityFileException when an uploaded PDF cannot be imported.
     */
    public function build(Model $owner, Authority $authority): AuthorityFileResult
    {
        $missing = $this->missing($owner, $authority);

        if ($missing !== []) {
            return new AuthorityFileResult(missing: $missing);
        }

        $items = $this->items($owner, $authority)->filter(fn (ChecklistItem $item): bool => $item->isUploaded())->values();
        $tempDir = storage_path('app/mpdf-tmp');
        File::ensureDirectoryExists($tempDir);
        $convertedFiles = [];

        try {
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'tempDir' => $tempDir,
                'autoScriptToLang' => true,
                'autoLangToFont' => true,
                'default_font' => 'dejavusans',
                'margin_left' => self::MARGIN,
                'margin_right' => self::MARGIN,
                'margin_top' => self::MARGIN,
                'margin_bottom' => self::MARGIN,
            ]);
            $mpdf->SetTitle($authority->trans('name'));
            $mpdf->SetDirectionality($this->isRtl() ? 'rtl' : 'ltr');

            $mpdf->WriteHTML(view('documents.pdf.cover', [
                'owner' => $owner,
                'ownerName' => $this->ownerName($owner),
                'authority' => $authority,
                'items' => $items,
            ])->render());

            foreach ($items as $item) {
                $file = $item->latestFile();
                $path = Storage::disk(DocumentFile::DISK)->path($file->file_path);

                if ($file->isImage()) {
                    $this->addImagePage($mpdf, $path);
                } else {
                    $convertedFiles[] = $this->addPdfPages($mpdf, $path, $item->name());
                }
            }

            $this->addCompletedVisasPage($mpdf, $owner, $authority);

            $pageCount = count($mpdf->pages);
            $content = $mpdf->Output('', Destination::STRING_RETURN);
        } finally {
            File::delete(array_filter($convertedFiles));
        }

        $path = config('documents.prints_directory').'/'.Str::uuid().'.pdf';
        Storage::disk(DocumentFile::DISK)->put($path, $content);

        $print = DocumentPrint::query()->create([
            'documentable_type' => $owner->getMorphClass(),
            'documentable_id' => $owner->getKey(),
            'authority_id' => $authority->id,
            'file_path' => $path,
            'printed_by' => Auth::id(),
            'printed_at' => now(),
        ]);

        $included = $items->map(fn (ChecklistItem $item): string => $item->name())->all();
        $this->audit->log('document.printed', $owner, ['authority' => $authority->translations('name')], new: ['documents' => $included]);

        return new AuthorityFileResult(print: $print, included: $included, pageCount: $pageCount);
    }

    private function addImagePage(Mpdf $mpdf, string $path): void
    {
        $this->addA4Page($mpdf);

        [$width, $height] = getimagesize($path) ?: [1, 1];
        $maxWidth = $mpdf->w - 2 * self::MARGIN;
        $maxHeight = $mpdf->h - 2 * self::MARGIN;
        $scale = min($maxWidth / $width, $maxHeight / $height);
        $drawWidth = $width * $scale;
        $drawHeight = $height * $scale;

        $mpdf->Image(
            $path,
            ($mpdf->w - $drawWidth) / 2,
            ($mpdf->h - $drawHeight) / 2,
            $drawWidth,
            $drawHeight,
            '', '', true, false,
        );
    }

    /**
     * Import every page of a PDF. Returns the temporary converted file, if any.
     *
     * @throws AuthorityFileException
     */
    private function addPdfPages(Mpdf $mpdf, string $path, string $name): ?string
    {
        $converted = null;

        try {
            $pages = $mpdf->setSourceFile($path);
        } catch (PdfParserException) {
            $converted = $this->convertForImport($path) ?? throw new AuthorityFileException($name);

            try {
                $pages = $mpdf->setSourceFile($converted);
            } catch (PdfParserException) {
                File::delete($converted);

                throw new AuthorityFileException($name);
            }
        }

        for ($page = 1; $page <= $pages; $page++) {
            $template = $mpdf->importPage($page);
            $size = $mpdf->getTemplateSize($template);

            $mpdf->AddPageByArray([
                'orientation' => $size['orientation'],
                'sheet-size' => [$size['width'], $size['height']],
                'margin-left' => 0, 'margin-right' => 0, 'margin-top' => 0, 'margin-bottom' => 0,
            ]);
            $mpdf->useTemplate($template, 0, 0, $size['width'], $size['height']);
        }

        return $converted;
    }

    /**
     * Rewrite a PDF 1.5+ file (object streams) so FPDI can read it, using
     * qpdf when installed. Returns null when conversion is not possible.
     */
    private function convertForImport(string $path): ?string
    {
        $target = storage_path('app/mpdf-tmp/'.Str::uuid().'.pdf');

        try {
            $result = Process::timeout(60)->run([
                config('documents.qpdf_binary'), '--object-streams=disable', '--force-version=1.4', $path, $target,
            ]);
        } catch (Throwable) {
            return null;
        }

        // qpdf exit code 3 = success with warnings.
        return in_array($result->exitCode(), [0, 3], true) && is_file($target) ? $target : null;
    }

    private function addCompletedVisasPage(Mpdf $mpdf, Model $owner, Authority $authority): void
    {
        $countryId = $owner instanceof DocumentOwner ? $owner->documentCountryId() : null;
        $country = $countryId ? Country::query()->find($countryId) : null;

        if (! $country) {
            return;
        }

        $setting = CompletedVisaListSetting::query()
            ->active()
            ->where('authority_id', $authority->id)
            ->where('country_id', $country->id)
            ->first();

        if (! $setting) {
            return;
        }

        $this->addA4Page($mpdf);
        $mpdf->WriteHTML(view('documents.pdf.completed-visas', [
            'authority' => $authority,
            'country' => $country,
            'visas' => $this->completedVisas->latest($authority, $country, $setting->visas_count),
            'count' => $setting->visas_count,
        ])->render());
    }

    private function addA4Page(Mpdf $mpdf): void
    {
        $mpdf->AddPageByArray([
            'orientation' => 'P',
            'sheet-size' => 'A4',
            'margin-left' => self::MARGIN, 'margin-right' => self::MARGIN,
            'margin-top' => self::MARGIN, 'margin-bottom' => self::MARGIN,
        ]);
    }

    private function ownerName(Model $owner): string
    {
        $name = $owner instanceof DocumentOwner ? $owner->documentOwnerName() : '#'.$owner->getKey();

        if (is_array($name)) {
            return (string) ($name[app()->getLocale()] ?: ($name['ar'] ?? $name['en'] ?? ''));
        }

        return $name;
    }

    private function isRtl(): bool
    {
        return (config('app.available_locales')[app()->getLocale()]['dir'] ?? 'ltr') === 'rtl';
    }
}
