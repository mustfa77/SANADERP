<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\Authority;
use App\Models\Candidate;
use App\Models\Country;
use App\Models\Document;
use App\Models\DocumentType;
use App\Services\Documents\DocumentChecklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    private Candidate $candidate;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seedAccess();
        Country::factory()->create(['code' => 'PH']);
        $this->candidate = Candidate::factory()->create(['nationality' => 'PH']);
    }

    private function storedDocument(): Document
    {
        return app(DocumentChecklistService::class)->upload($this->candidate, DocumentType::factory()->create()->id, UploadedFile::fake()->image('passport.png'));
    }

    public function test_files_cannot_be_accessed_without_permission(): void
    {
        $file = $this->storedDocument()->latestFile;
        $url = route('admin.documents.files.show', $file);

        $this->get($url)->assertRedirect(route('login'));

        $noDocuments = $this->userWithRole('rental_team', ['RYD']);
        $this->actingAs($noDocuments)->get($url)->assertForbidden();

        // documents.view alone is not enough: the owner permission is checked too.
        $documentsOnly = $this->userWithRole('rental_team', ['RYD']);
        $documentsOnly->givePermissionTo('documents.view');
        $this->actingAs($documentsOnly)->get($url)->assertForbidden();

        $allowed = $this->userWithRole('rental_team', ['RYD']);
        $allowed->givePermissionTo(['documents.view', 'candidates.view']);
        $this->actingAs($allowed)->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
    }

    public function test_files_are_not_publicly_reachable(): void
    {
        $file = $this->storedDocument()->latestFile;

        Storage::disk('local')->assertExists($file->file_path);
        Storage::disk('public')->assertMissing($file->file_path);
        $this->get('/storage/'.$file->file_path)->assertNotFound();
    }

    public function test_upload_and_replace_through_the_checklist(): void
    {
        $admin = $this->userWithRole('super_admin');
        $type = DocumentType::factory()->create();
        $url = route('admin.documents.upload', ['candidate', $this->candidate->id, $type]);

        $this->actingAs($admin)->post($url, ['file' => UploadedFile::fake()->create('scan.pdf', 100, 'application/pdf')])->assertSessionHas('success');
        $this->actingAs($admin)->post($url, ['file' => UploadedFile::fake()->image('scan.jpg')])->assertSessionHas('success');

        $document = $this->candidate->documents()->sole();
        $this->assertSame(2, $document->current_version);
        $this->assertSame(2, $document->files()->count());
    }

    public function test_replacing_needs_the_replace_permission(): void
    {
        $user = $this->userWithRole('rental_team', ['RYD']);
        $user->givePermissionTo(['documents.upload', 'candidates.view']);
        $type = DocumentType::factory()->create();
        $url = route('admin.documents.upload', ['candidate', $this->candidate->id, $type]);

        $this->actingAs($user)->post($url, ['file' => UploadedFile::fake()->image('a.png')])->assertSessionHas('success');
        $this->actingAs($user)->post($url, ['file' => UploadedFile::fake()->image('b.png')])->assertForbidden();
    }

    public function test_rejects_disallowed_file_types_and_oversized_files(): void
    {
        $admin = $this->userWithRole('super_admin');
        $type = DocumentType::factory()->create();
        $url = route('admin.documents.upload', ['candidate', $this->candidate->id, $type]);

        $this->actingAs($admin)->post($url, ['file' => UploadedFile::fake()->create('script.exe', 10, 'application/x-msdownload')])
            ->assertSessionHasErrors('document_'.$type->id);
        $this->actingAs($admin)->post($url, ['file' => UploadedFile::fake()->create('big.pdf', 11 * 1024, 'application/pdf')])
            ->assertSessionHasErrors('document_'.$type->id);

        $this->assertSame(0, $this->candidate->documents()->count());
    }

    public function test_unknown_owner_type_is_not_found(): void
    {
        $admin = $this->userWithRole('super_admin');
        $type = DocumentType::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.documents.upload', ['user', $admin->id, $type]), ['file' => UploadedFile::fake()->image('a.png')])
            ->assertNotFound();
    }

    public function test_print_button_shows_missing_documents_and_print_downloads_the_file(): void
    {
        $admin = $this->userWithRole('super_admin');
        $authority = Authority::factory()->create();
        $type = DocumentType::factory()->required()->inAuthorityFile()->create();
        $printUrl = route('admin.documents.print', ['candidate', $this->candidate->id, $authority]);

        $this->actingAs($admin)->from('/admin')->post($printUrl)
            ->assertRedirect('/admin')
            ->assertSessionHas('authority_file_missing', ['authority_id' => $authority->id, 'names' => [$type->trans('name')]]);

        app(DocumentChecklistService::class)->upload($this->candidate, $type->id, UploadedFile::fake()->image('passport.jpg'));

        $this->actingAs($admin)->post($printUrl)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame(1, $this->candidate->documentPrints()->count());
    }

    public function test_deleting_a_version_needs_permission_and_keeps_the_last_one(): void
    {
        $service = app(DocumentChecklistService::class);
        $type = DocumentType::factory()->create();
        $service->upload($this->candidate, $type->id, UploadedFile::fake()->image('v1.png'));
        $document = $service->upload($this->candidate, $type->id, UploadedFile::fake()->image('v2.png'));
        [$latest, $previous] = $document->files->all();

        $viewer = $this->userWithRole('rental_team', ['RYD']);
        $viewer->givePermissionTo(['documents.view', 'candidates.view']);
        $this->actingAs($viewer)->delete(route('admin.documents.files.destroy', $previous))->assertForbidden();

        $admin = $this->userWithRole('super_admin');
        $this->actingAs($admin)->delete(route('admin.documents.files.destroy', $latest))->assertSessionHas('success');
        $this->assertSame(1, $document->fresh()->current_version);

        $this->actingAs($admin)->delete(route('admin.documents.files.destroy', $previous))->assertSessionHas('error');
    }
}
