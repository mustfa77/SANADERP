<?php

namespace Tests\Feature\Services\Documents;

use App\Models\Authority;
use App\Models\Candidate;
use App\Models\CompletedVisaListSetting;
use App\Models\Country;
use App\Models\DocumentPrint;
use App\Models\DocumentType;
use App\Services\Documents\AuthorityFilePrintService;
use App\Services\Documents\DocumentChecklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Tests\TestCase;

class AuthorityFilePrintServiceTest extends TestCase
{
    use RefreshDatabase;

    private Country $country;

    private Authority $authority;

    private Candidate $candidate;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        app()->setLocale('en');
        $this->country = Country::factory()->create(['code' => 'PH']);
        $this->authority = Authority::factory()->create(['country_id' => $this->country->id]);
        $this->candidate = Candidate::factory()->create(['nationality' => 'PH', 'name_en' => 'Maria Santos', 'name_ar' => 'ماريا']);
    }

    /**
     * A small 2-page PDF made with mPDF (PDF 1.4, importable by FPDI).
     */
    private function pdfUpload(string $name): UploadedFile
    {
        $mpdf = new Mpdf(['tempDir' => storage_path('app/mpdf-tmp')]);
        $mpdf->WriteHTML('<p>Page one</p>');
        $mpdf->AddPage();
        $mpdf->WriteHTML('<p>Page two</p>');

        return UploadedFile::fake()->createWithContent($name, $mpdf->Output('', Destination::STRING_RETURN));
    }

    private function upload(DocumentType $type, UploadedFile $file): void
    {
        app(DocumentChecklistService::class)->upload($this->candidate, $type->id, $file);
    }

    public function test_print_is_blocked_when_a_required_included_document_is_missing(): void
    {
        DocumentType::factory()->required()->inAuthorityFile()->create(['name_en' => 'Passport']);
        DocumentType::factory()->required()->create(['name_en' => 'Not in file']);

        $result = app(AuthorityFilePrintService::class)->build($this->candidate, $this->authority);

        $this->assertTrue($result->isBlocked());
        $this->assertSame(['Passport'], $result->missing);
        $this->assertSame(0, DocumentPrint::query()->count());
    }

    public function test_documents_of_another_authority_are_not_required(): void
    {
        $other = Authority::factory()->create(['country_id' => $this->country->id]);
        DocumentType::factory()->required()->inAuthorityFile()->create(['name_en' => 'Other office form', 'authority_id' => $other->id]);

        $this->assertSame([], app(AuthorityFilePrintService::class)->missing($this->candidate, $this->authority));
        $this->assertSame(['Other office form'], app(AuthorityFilePrintService::class)->missing($this->candidate, $other));
    }

    public function test_printed_pdf_contains_documents_in_configured_order(): void
    {
        $contract = DocumentType::factory()->required()->inAuthorityFile()->create(['name_en' => 'Contract', 'country_id' => $this->country->id]);
        $passport = DocumentType::factory()->required()->inAuthorityFile()->create(['name_en' => 'Passport']);
        $photo = DocumentType::factory()->inAuthorityFile()->create(['name_en' => 'Photo']);
        DocumentType::factory()->create(['name_en' => 'Internal note']);

        $this->upload($contract, $this->pdfUpload('contract.pdf'));
        $this->upload($passport, UploadedFile::fake()->image('passport.jpg', 600, 800));
        $this->upload($photo, UploadedFile::fake()->image('photo.png', 400, 400));

        // Photo before passport among the global types.
        $photo->move(-1);

        CompletedVisaListSetting::factory()->create(['authority_id' => $this->authority->id, 'country_id' => $this->country->id]);

        $result = app(AuthorityFilePrintService::class)->build($this->candidate, $this->authority);

        $this->assertFalse($result->isBlocked());
        $this->assertSame(['Photo', 'Passport', 'Contract'], $result->included);

        // Cover + photo + passport + 2 contract pages + completed visas page.
        $this->assertSame(6, $result->pageCount);

        $print = $result->print;
        Storage::disk('local')->assertExists($print->file_path);
        $this->assertTrue($print->documentable->is($this->candidate));

        $reader = new Mpdf(['tempDir' => storage_path('app/mpdf-tmp')]);
        $this->assertSame(6, $reader->setSourceFile(Storage::disk('local')->path($print->file_path)));
    }

    public function test_completed_visas_page_is_only_added_when_configured(): void
    {
        $passport = DocumentType::factory()->inAuthorityFile()->create();
        $this->upload($passport, UploadedFile::fake()->image('passport.jpg'));

        $result = app(AuthorityFilePrintService::class)->build($this->candidate, $this->authority);

        $this->assertSame(2, $result->pageCount);
    }
}
