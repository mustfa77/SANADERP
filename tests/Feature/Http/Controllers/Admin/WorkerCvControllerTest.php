<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Enums\SponsorType;
use App\Enums\WorkerStatus;
use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\Country;
use App\Models\Profession;
use App\Models\User;
use App\Models\Worker;
use App\Services\Workers\WorkerStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkerCvControllerTest extends TestCase
{
    use RefreshDatabase;

    private Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seedAccess();
        $this->country = Country::factory()->create(['code' => 'LK']);
    }

    private function worker(string $template = 'housemaid', array $attributes = []): Worker
    {
        return Worker::factory()->create([
            'branch_id' => $this->branch('RYD')->id,
            'country_id' => $this->country->id,
            'profession_id' => Profession::factory()->create(['cv_template' => $template])->id,
            ...$attributes,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function cvPayload(array $overrides = []): array
    {
        return [
            'cv_reference_no' => 'AR-01',
            'cv_category' => 'first_timer',
            'home_city' => 'Kalutara',
            'education' => 'Grade 10',
            'children_count' => 2,
            'height_cm' => 155,
            'weight_kg' => 54,
            'arabic_level' => 'none',
            'english_level' => 'weak',
            'cv_skills' => ['help_kids', 'home_cleaning', 'washing_ironing'],
            'experiences' => [
                ['country' => 'Kuwait', 'position' => 'Housemaid', 'duration' => '2 years'],
                ['country' => '', 'position' => '', 'duration' => ''],
            ],
            ...$overrides,
        ];
    }

    public function test_staff_fill_in_the_cv_with_the_template_skills_and_photos(): void
    {
        $admin = $this->userWithRole('super_admin');
        $worker = $this->worker();

        $this->actingAs($admin)->put(route('admin.workers.cv.update', $worker), $this->cvPayload([
            'photo' => UploadedFile::fake()->image('face.jpg', 300, 400),
            'full_photo' => UploadedFile::fake()->image('full.jpg', 400, 900),
        ]))->assertRedirect(route('admin.workers.show', [$worker, 'tab' => 'cv']));

        $worker->refresh();
        $this->assertSame('AR-01', $worker->cv_reference_no);
        // Stored in the template order.
        $this->assertSame(['help_kids', 'home_cleaning', 'washing_ironing'], $worker->cv_skills);
        $this->assertSame(['Kuwait'], $worker->experiences()->pluck('country')->all());
        Storage::disk('local')->assertExists($worker->photo_path);
        Storage::disk('local')->assertExists($worker->full_photo_path);
        $this->assertSame($admin->id, $worker->cv_updated_by);
        $this->assertDatabaseHas(AuditLog::class, ['action_key' => 'worker.cv_updated', 'target_id' => $worker->id]);

        $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.workers.show', [$worker, 'tab' => 'cv']))
            ->assertOk()
            ->assertSee('Kalutara')
            ->assertSee(__('cv.skills.home_cleaning', [], 'en'));
    }

    public function test_skills_of_another_template_are_rejected(): void
    {
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->put(route('admin.workers.cv.update', $this->worker()), $this->cvPayload(['cv_skills' => ['sedan']]))
            ->assertSessionHasErrors('cv_skills.0');
    }

    public function test_driver_cv_has_driving_skills_and_licence_details(): void
    {
        $admin = $this->userWithRole('super_admin');
        $driver = $this->worker('driver', ['gender' => 'male']);

        $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.workers.cv.edit', $driver))
            ->assertOk()
            ->assertSee('cv_details[license_number]', false)
            ->assertSee(__('cv.skills.saudi_traffic_laws', [], 'en'))
            ->assertSee(__('cv.documents_hint.driver', [], 'en'));

        $this->actingAs($admin)->put(route('admin.workers.cv.update', $driver), $this->cvPayload([
            'cv_skills' => ['sedan', 'suv', 'saudi_license'],
            'cv_details' => ['license_number' => 'DL-12345', 'test_video_url' => 'https://example.com/test.mp4', 'unknown' => 'x'],
        ]))->assertRedirect();

        $driver->refresh();
        $this->assertSame(['sedan', 'suv', 'saudi_license'], $driver->cv_skills);
        // Unknown keys are dropped.
        $this->assertSame(['license_number' => 'DL-12345', 'test_video_url' => 'https://example.com/test.mp4'], $driver->cv_details);

        $this->actingAs($admin)->put(route('admin.workers.cv.update', $driver), $this->cvPayload(['cv_skills' => [], 'cv_details' => ['test_video_url' => 'not a link']]))
            ->assertSessionHasErrors('cv_details.test_video_url');
    }

    public function test_house_boy_experience_uses_the_city(): void
    {
        $admin = $this->userWithRole('super_admin');
        $houseBoy = $this->worker('house_boy', ['gender' => 'male']);

        $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.workers.cv.edit', $houseBoy))
            ->assertSee(__('cv.experience.city', [], 'en'))
            ->assertSee(__('cv.skills.gardening', [], 'en'));
    }

    public function test_care_giver_cv_has_detailed_experience_and_no_skills(): void
    {
        $admin = $this->userWithRole('super_admin');
        $careGiver = $this->worker('care_giver');

        $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.workers.cv.edit', $careGiver))
            ->assertSee('experiences[0][company]', false)
            ->assertDontSee('name="cv_skills[]"', false);

        $this->actingAs($admin)->put(route('admin.workers.cv.update', $careGiver), $this->cvPayload([
            'cv_skills' => [],
            'experiences' => [
                [
                    'position' => 'Caregiver', 'company' => 'Al Noor Hospital', 'location' => 'Manila',
                    'started_at' => '2019-03-01', 'ended_at' => '2022-05-31',
                    'description' => "Patient care\nGiving medicines on time",
                ],
                ['position' => '', 'company' => '', 'location' => '', 'started_at' => '', 'ended_at' => '', 'description' => ''],
            ],
        ]))->assertRedirect();

        $experience = $careGiver->experiences()->sole();
        $this->assertSame('Al Noor Hospital', $experience->company);
        $this->assertSame('2019-03 → 2022-05', $experience->period());

        // The end date cannot be before the start.
        $this->actingAs($admin)->put(route('admin.workers.cv.update', $careGiver), $this->cvPayload([
            'experiences' => [['position' => 'Caregiver', 'started_at' => '2022-01-01', 'ended_at' => '2021-01-01']],
        ]))->assertSessionHasErrors('experiences.0.ended_at');

        $this->actingAs($admin)->get(route('admin.workers.cv.pdf', $careGiver))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_cv_pdf_is_generated(): void
    {
        $admin = $this->userWithRole('super_admin');
        $worker = $this->worker();
        $this->actingAs($admin)->put(route('admin.workers.cv.update', $worker), $this->cvPayload(['photo' => UploadedFile::fake()->image('face.jpg')]));

        $response = $this->actingAs($admin)->get(route('admin.workers.cv.pdf', $worker));

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        // Downloaded directly, not opened in the browser.
        $this->assertStringStartsWith('attachment; filename=CV-'.$worker->number(), $response->headers->get('Content-Disposition'));
    }

    public function test_cv_opens_as_a_page_with_print_and_download_buttons(): void
    {
        $admin = $this->userWithRole('super_admin');
        $worker = $this->worker('housemaid', ['name_en' => 'Preview Worker']);
        $this->actingAs($admin)->put(route('admin.workers.cv.update', $worker), $this->cvPayload(['photo' => UploadedFile::fake()->image('face.jpg')]));

        $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.workers.show', [$worker, 'tab' => 'cv']))
            ->assertSee(route('admin.workers.cv.preview', $worker), false);

        $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.workers.cv.preview', $worker))
            ->assertOk()
            ->assertSee('Preview Worker')
            ->assertSee('Kalutara')
            ->assertSee('window.print()', false)
            ->assertSee(route('admin.workers.cv.pdf', $worker), false)
            ->assertSee(route('admin.workers.photo', $worker), false);
    }

    public function test_agency_opens_the_cv_page_of_its_own_workers_only(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->externalAgency($agency)->create(['preferred_language' => 'en']);
        $own = $this->worker('housemaid', ['agency_id' => $agency->id, 'name_en' => 'Own Worker']);
        $other = $this->worker('housemaid', ['agency_id' => Agency::factory()->create()->id]);

        $this->actingAs($user)->get(route('agency.workers.preview', $own->id))
            ->assertOk()
            ->assertSee('Own Worker')
            ->assertSee(route('agency.workers.pdf', $own->id), false);
        $this->actingAs($user)->get(route('agency.workers.preview', $other->id))->assertNotFound();
    }

    public function test_agency_fills_the_cv_of_its_own_workers_only(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->externalAgency($agency)->create(['preferred_language' => 'en']);
        $own = $this->worker('housemaid', ['agency_id' => $agency->id, 'name_en' => 'Own Worker']);
        $other = $this->worker('housemaid', ['agency_id' => Agency::factory()->create()->id, 'name_en' => 'Other Agency Worker']);

        $this->actingAs($user)->get(route('agency.workers.index'))
            ->assertOk()
            ->assertSee('Own Worker')
            ->assertDontSee('Other Agency Worker');

        $this->actingAs($user)->get(route('agency.workers.edit', $other->id))->assertNotFound();
        $this->actingAs($user)->put(route('agency.workers.update', $other->id), $this->cvPayload())->assertNotFound();

        $this->actingAs($user)->put(route('agency.workers.update', $own->id), $this->cvPayload())->assertRedirect(route('agency.workers.edit', $own->id));
        $this->assertSame('AR-01', $own->fresh()->cv_reference_no);

        // The portal never shows the passport number.
        $this->actingAs($user)->get(route('agency.workers.edit', $own->id))->assertDontSee($own->passport_no);
    }

    public function test_staff_cannot_use_the_agency_portal(): void
    {
        $this->actingAs($this->userWithRole('super_admin'))->get(route('agency.workers.index'))->assertForbidden();
    }

    public function test_direct_order_button_opens_the_order_with_the_worker_selected(): void
    {
        $admin = $this->userWithRole('super_admin');
        $worker = $this->worker('housemaid', ['sponsor_type' => SponsorType::Client, 'name_en' => 'Ready Worker']);
        app(WorkerStatusService::class)->transition($worker, WorkerStatus::UnderReview);
        app(WorkerStatusService::class)->transition($worker, WorkerStatus::ReadyForSelection);
        $url = route('admin.recruitment.create', ['worker' => $worker->id]);

        $this->actingAs($admin)->get(route('admin.workers.index', ['group' => 'recruitment']))->assertSee($url, false);
        $this->actingAs($admin)->get(route('admin.workers.show', $worker))->assertSee($url, false);

        $this->actingAs($admin)->get($url)->assertOk()->assertSee('data-selected="'.$worker->id.'"', false);
    }

    public function test_workers_can_be_found_by_worker_number_or_reference(): void
    {
        $admin = $this->userWithRole('super_admin');
        $match = $this->worker('housemaid', ['name_en' => 'Numbered Worker']);
        $match->forceFill(['cv_reference_no' => 'AR-77'])->save();
        $this->worker('housemaid', ['name_en' => 'Someone Else']);

        foreach ([$match->number(), 'AR-77'] as $search) {
            $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.workers.index', ['search' => $search]))
                ->assertSee('Numbered Worker')
                ->assertDontSee('Someone Else');
        }
    }
}
