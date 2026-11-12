<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Enums\SponsorType;
use App\Enums\WorkerStatus;
use App\Models\Client;
use App\Models\Country;
use App\Models\DocumentType;
use App\Models\Profession;
use App\Models\Worker;
use App\Services\Documents\DocumentChecklistService;
use App\Services\Recruitment\RecruitmentService;
use App\Services\Workers\WorkerStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RecruitmentCandidateControllerTest extends TestCase
{
    use RefreshDatabase;

    private Country $country;

    private Profession $profession;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seedAccess();
        $this->country = Country::factory()->create(['code' => 'KE']);
        $this->profession = Profession::factory()->create();
    }

    private function worker(string $name, ?WorkerStatus $status = WorkerStatus::ReadyForSelection, string $branch = 'RYD', array $attributes = []): Worker
    {
        $worker = Worker::factory()->create([
            'name_en' => $name,
            'branch_id' => $this->branch($branch)->id,
            'country_id' => $this->country->id,
            'profession_id' => $this->profession->id,
            'sponsor_type' => SponsorType::Client,
            ...$attributes,
        ]);

        $statuses = app(WorkerStatusService::class);
        $path = match ($status) {
            WorkerStatus::UnderReview => [WorkerStatus::UnderReview],
            WorkerStatus::ReadyForSelection => [WorkerStatus::UnderReview, WorkerStatus::ReadyForSelection],
            WorkerStatus::Rejected => [WorkerStatus::Rejected],
            default => [],
        };

        foreach ($path as $step) {
            $statuses->transition($worker, $step);
        }

        return $worker;
    }

    public function test_ready_candidates_lists_only_selectable_workers_of_the_users_branches(): void
    {
        $this->worker('Ready Amina');
        $this->worker('Under Review Joy', WorkerStatus::UnderReview);
        $this->worker('Company Grace', attributes: ['sponsor_type' => SponsorType::Company]);
        $this->worker('Qassim Mary', branch: 'QSM');
        $held = $this->worker('Held Faith');
        $client = Client::factory()->create(['branch_id' => $this->branch('RYD')->id]);
        $service = app(RecruitmentService::class);
        $service->selectCandidate($service->create([
            'branch_id' => $client->branch_id, 'client_id' => $client->id, 'country_id' => $this->country->id, 'profession_id' => $this->profession->id,
        ]), $held);

        $user = $this->userWithRole('branch_manager', ['RYD']);
        $user->givePermissionTo('workers.view');

        $this->actingAs($user)->withSession(['locale' => 'en'])->get(route('admin.recruitment.ready'))
            ->assertOk()
            ->assertSee('Ready Amina')
            ->assertDontSee('Under Review Joy')
            ->assertDontSee('Company Grace')
            ->assertDontSee('Qassim Mary')
            ->assertDontSee('Held Faith');
    }

    public function test_ready_candidates_show_the_cv_and_direct_order_links_and_can_be_searched(): void
    {
        $admin = $this->userWithRole('super_admin');
        $amina = $this->worker('Ready Amina');
        $this->worker('Ready Zawadi');

        $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.recruitment.ready', ['search' => $amina->number()]))
            ->assertOk()
            ->assertSee('Ready Amina')
            ->assertDontSee('Ready Zawadi')
            ->assertSee(route('admin.workers.cv.preview', $amina), false)
            ->assertSee(route('admin.recruitment.create', ['worker' => $amina->id]), false);
    }

    public function test_documents_page_shows_the_missing_required_documents(): void
    {
        $admin = $this->userWithRole('super_admin');
        $passport = DocumentType::factory()->required()->create(['country_id' => $this->country->id, 'name_en' => 'Passport copy']);
        DocumentType::factory()->required()->create(['country_id' => $this->country->id, 'name_en' => 'Medical report']);
        DocumentType::factory()->create(['country_id' => $this->country->id, 'name_en' => 'Optional photo']);

        $complete = $this->worker('Complete Wanjiru', WorkerStatus::UnderReview);
        $missing = $this->worker('Missing Achieng', WorkerStatus::UnderReview);
        $this->worker('Rejected Nekesa', WorkerStatus::Rejected);

        $checklist = app(DocumentChecklistService::class);
        foreach (DocumentType::query()->where('is_required', true)->get() as $type) {
            $checklist->upload($complete, $type->id, UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'));
        }
        $checklist->upload($missing, $passport->id, UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'));

        $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.recruitment.documents'))
            ->assertOk()
            ->assertSee('Complete Wanjiru')
            ->assertSee('Missing Achieng')
            ->assertDontSee('Rejected Nekesa')
            ->assertSee('Medical report')
            ->assertDontSee('Optional photo')
            ->assertSee(route('admin.workers.show', [$missing, 'tab' => 'documents']), false);

        $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.recruitment.documents', ['completeness' => 'incomplete']))
            ->assertSee('Missing Achieng')
            ->assertDontSee('Complete Wanjiru');
    }

    public function test_pages_need_permission(): void
    {
        // Marketing sees candidates but not the workers file.
        $user = $this->userWithRole('marketing_team', ['RYD']);

        $this->actingAs($user)->get(route('admin.recruitment.ready'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.recruitment.documents'))->assertForbidden();
    }
}
