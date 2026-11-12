<?php

namespace Tests\Feature\Services\Documents;

use App\Models\Candidate;
use App\Models\Country;
use App\Models\DocumentType;
use App\Services\Documents\DocumentChecklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentChecklistServiceTest extends TestCase
{
    use RefreshDatabase;

    private DocumentChecklistService $service;

    private Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->service = app(DocumentChecklistService::class);
        $this->country = Country::factory()->create(['code' => 'PH']);
    }

    private function candidate(): Candidate
    {
        return Candidate::factory()->create(['nationality' => 'PH']);
    }

    /**
     * @return list<string>
     */
    private function names(Candidate $candidate): array
    {
        return $this->service->required($candidate)->map->name()->all();
    }

    public function test_lists_global_types_first_then_country_types_in_sort_order(): void
    {
        DocumentType::factory()->create(['name_en' => 'Country A', 'country_id' => $this->country->id]);
        DocumentType::factory()->create(['name_en' => 'Global A']);
        DocumentType::factory()->create(['name_en' => 'Other country', 'country_id' => Country::factory()->create()->id]);
        DocumentType::factory()->create(['name_en' => 'Global B']);

        app()->setLocale('en');

        $this->assertSame(['Global A', 'Global B', 'Country A'], $this->names($this->candidate()));
    }

    public function test_reordering_document_types_changes_checklist_order(): void
    {
        app()->setLocale('en');
        $passport = DocumentType::factory()->create(['name_en' => 'Passport']);
        $photo = DocumentType::factory()->create(['name_en' => 'Photo']);
        $medical = DocumentType::factory()->create(['name_en' => 'Medical']);
        $candidate = $this->candidate();

        $this->assertSame(['Passport', 'Photo', 'Medical'], $this->names($candidate));

        $medical->move(-1);
        $this->assertSame(['Passport', 'Medical', 'Photo'], $this->names($candidate));

        $passport->reorderGroup([$photo->id, $medical->id, $passport->id]);
        $this->assertSame(['Photo', 'Medical', 'Passport'], $this->names($candidate));
    }

    public function test_deactivated_type_is_hidden_for_new_owners_but_existing_files_stay_visible(): void
    {
        app()->setLocale('en');
        $type = DocumentType::factory()->create(['name_en' => 'Old certificate']);
        $existing = $this->candidate();
        $this->service->upload($existing, $type->id, UploadedFile::fake()->create('cert.pdf', 50, 'application/pdf'));

        $type->update(['is_active' => false]);

        $this->assertNotContains('Old certificate', $this->names($this->candidate()));

        $item = $this->service->required($existing)->sole();
        $this->assertSame('Old certificate', $item->name());
        $this->assertTrue($item->isUploaded());
        $this->assertFalse($item->isRequired());
    }

    public function test_replace_keeps_previous_version(): void
    {
        $type = DocumentType::factory()->create();
        $candidate = $this->candidate();

        $first = $this->service->upload($candidate, $type->id, UploadedFile::fake()->create('v1.pdf', 40, 'application/pdf'));
        $second = $this->service->upload($candidate, $type->id, UploadedFile::fake()->image('v2.jpg'));

        $this->assertTrue($first->is($second));
        $this->assertSame(2, $second->current_version);
        $this->assertSame([2, 1], $second->files()->pluck('version')->all());
        $this->assertSame('v2.jpg', $second->latestFile->original_name);

        foreach ($second->files as $file) {
            Storage::disk('local')->assertExists($file->file_path);
        }
    }

    public function test_missing_required_lists_only_required_documents_not_uploaded(): void
    {
        app()->setLocale('en');
        $passport = DocumentType::factory()->required()->create(['name_en' => 'Passport']);
        DocumentType::factory()->required()->create(['name_en' => 'Medical', 'country_id' => $this->country->id]);
        DocumentType::factory()->create(['name_en' => 'Optional photo']);
        $candidate = $this->candidate();

        $this->service->upload($candidate, $passport->id, UploadedFile::fake()->image('passport.png'));

        $this->assertSame(['Medical'], $this->service->missingRequired($candidate));
    }

    public function test_cannot_delete_the_only_version(): void
    {
        $document = $this->service->upload($this->candidate(), DocumentType::factory()->create()->id, UploadedFile::fake()->image('a.png'));

        $this->expectException(\RuntimeException::class);
        $this->service->deleteVersion($document->latestFile);
    }
}
