<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Enums\LookupType;
use App\Enums\RecruitmentStatus;
use App\Enums\SponsorType;
use App\Enums\WorkerStatus;
use App\Models\AuditLog;
use App\Models\Camp;
use App\Models\Client;
use App\Models\Country;
use App\Models\Lookup;
use App\Models\Profession;
use App\Models\RecruitmentOrder;
use App\Models\Setting;
use App\Models\Worker;
use App\Services\Recruitment\RecruitmentService;
use App\Services\Workers\WorkerStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecruitmentOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private Country $country;

    private Profession $profession;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();
        $this->country = Country::factory()->create(['code' => 'PH']);
        $this->profession = Profession::factory()->create();
    }

    private function client(string $branch = 'RYD'): Client
    {
        return Client::factory()->create(['branch_id' => $this->branch($branch)->id]);
    }

    private function order(Client $client): RecruitmentOrder
    {
        return app(RecruitmentService::class)->create([
            'branch_id' => $client->branch_id,
            'client_id' => $client->id,
            'country_id' => $this->country->id,
            'profession_id' => $this->profession->id,
        ]);
    }

    private function candidate(string $branch = 'RYD', array $attributes = []): Worker
    {
        $worker = Worker::factory()->create([
            'branch_id' => $this->branch($branch)->id,
            'country_id' => $this->country->id,
            'profession_id' => $this->profession->id,
            'sponsor_type' => SponsorType::Client,
            ...$attributes,
        ]);
        app(WorkerStatusService::class)->transition($worker, WorkerStatus::UnderReview);
        app(WorkerStatusService::class)->transition($worker, WorkerStatus::ReadyForSelection);

        return $worker;
    }

    /**
     * @return array<string, mixed>
     */
    private function newClientPayload(array $overrides = []): array
    {
        return [
            'client_mode' => 'new',
            'client' => [
                'name_ar' => 'فهد الدوسري',
                'name_en' => 'Fahad Al-Dosari',
                'national_id' => '1012345678',
                'phone' => '0551234567',
                'branch_id' => $this->branch('RYD')->id,
            ],
            'country_id' => $this->country->id,
            'profession_id' => $this->profession->id,
            ...$overrides,
        ];
    }

    public function test_recruitment_needs_permission(): void
    {
        $user = $this->userWithRole('rental_team', ['RYD']);

        $this->actingAs($user)->get(route('admin.recruitment.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.recruitment.store'), [])->assertForbidden();
    }

    public function test_creates_an_order_for_an_existing_client_in_the_clients_branch(): void
    {
        $admin = $this->userWithRole('super_admin');
        $client = $this->client('QSM');

        $this->actingAs($admin)->post(route('admin.recruitment.store'), [
            'client_mode' => 'existing', 'client_id' => $client->id, 'country_id' => $this->country->id, 'profession_id' => $this->profession->id,
        ])->assertRedirect();

        $order = RecruitmentOrder::query()->sole();
        $this->assertSame($this->branch('QSM')->id, $order->branch_id);
        $this->assertSame(RecruitmentStatus::New, $order->status);
        $this->assertDatabaseHas(AuditLog::class, ['action_key' => 'recruitment.created', 'target_id' => $order->id]);
    }

    public function test_walk_in_client_and_chosen_worker_are_saved_in_one_step(): void
    {
        $admin = $this->userWithRole('super_admin');
        $worker = $this->candidate();

        $this->actingAs($admin)->post(route('admin.recruitment.store'), $this->newClientPayload([
            'client' => [...$this->newClientPayload()['client'], 'national_id' => ' 10123 45678 '],
            'worker_id' => $worker->id,
        ]))->assertRedirect();

        $client = Client::query()->sole();
        $this->assertSame('1012345678', $client->national_id);
        $this->assertSame($admin->id, $client->created_by);

        $order = RecruitmentOrder::query()->sole();
        $this->assertTrue($order->client->is($client));
        $this->assertSame(RecruitmentStatus::CandidateSelected, $order->status);
        $this->assertTrue($order->worker->is($worker));
        $this->assertSame(WorkerStatus::OnHold, $worker->fresh()->status);
        $this->assertDatabaseHas(AuditLog::class, ['action_key' => 'client.created', 'target_id' => $client->id]);
    }

    public function test_new_client_is_validated_like_the_client_screen(): void
    {
        $admin = $this->userWithRole('super_admin');
        Client::factory()->create(['national_id' => '1012345678']);

        $this->actingAs($admin)->post(route('admin.recruitment.store'), $this->newClientPayload())
            ->assertSessionHasErrors(['client.national_id' => __('clients.validation.duplicate_national_id')]);

        $this->actingAs($admin)->post(route('admin.recruitment.store'), $this->newClientPayload(['client' => ['national_id' => '123']]))
            ->assertSessionHasErrors(['client.name_ar', 'client.national_id', 'client.phone']);

        $this->assertSame(0, RecruitmentOrder::query()->count());
    }

    public function test_an_unavailable_worker_rolls_back_the_new_client_and_the_order(): void
    {
        $admin = $this->userWithRole('super_admin');
        $otherCountry = $this->candidate('RYD', ['country_id' => Country::factory()->create()->id]);

        $this->actingAs($admin)->from(route('admin.recruitment.create'))
            ->post(route('admin.recruitment.store'), $this->newClientPayload(['worker_id' => $otherCountry->id]))
            ->assertRedirect(route('admin.recruitment.create'))
            ->assertSessionHas('error', __('recruitment.errors.candidate_not_eligible'))
            ->assertSessionHasInput('client.name_ar', 'فهد الدوسري');

        $this->assertSame(0, Client::query()->count());
        $this->assertSame(0, RecruitmentOrder::query()->count());
        $this->assertSame(WorkerStatus::ReadyForSelection, $otherCountry->fresh()->status);
    }

    public function test_adding_a_new_client_needs_the_client_create_permission(): void
    {
        // The accounts role can view clients but not create them.
        $user = $this->userWithRole('accounts', ['RYD']);
        $user->givePermissionTo(['recruitment.view', 'recruitment.create']);

        $this->actingAs($user)->get(route('admin.recruitment.create'))->assertOk()->assertDontSee('client_mode_new');
        $this->actingAs($user)->post(route('admin.recruitment.store'), $this->newClientPayload())->assertForbidden();
    }

    public function test_create_screen_lists_selectable_workers(): void
    {
        $admin = $this->userWithRole('super_admin');
        $this->candidate('RYD', ['name_en' => 'Ready Maria']);
        Worker::factory()->create(['name_en' => 'Not Screened', 'sponsor_type' => SponsorType::Client]);

        $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.recruitment.create'))
            ->assertOk()
            ->assertSee('Ready Maria')
            ->assertDontSee('Not Screened');
    }

    public function test_cannot_create_an_order_for_a_client_of_another_branch(): void
    {
        $riyadh = $this->userWithRole('branch_manager', ['RYD']);
        $riyadh->givePermissionTo('recruitment.create');

        $this->actingAs($riyadh)->post(route('admin.recruitment.store'), [
            'client_mode' => 'existing', 'client_id' => $this->client('QSM')->id, 'country_id' => $this->country->id, 'profession_id' => $this->profession->id,
        ])->assertSessionHasErrors('client_id');
    }

    public function test_riyadh_user_gets_404_on_a_qassim_order(): void
    {
        $order = $this->order($this->client('QSM'));
        $riyadh = $this->userWithRole('branch_manager', ['RYD']);

        $this->actingAs($riyadh)->get(route('admin.recruitment.show', $order))->assertNotFound();
        $this->actingAs($riyadh)->post(route('admin.recruitment.cancel', $order), [])->assertNotFound();
    }

    public function test_contract_records_the_visa_number_on_the_order(): void
    {
        $admin = $this->userWithRole('super_admin');
        $client = $this->client();
        $first = app(RecruitmentService::class)->selectCandidate($this->order($client), $this->candidate());
        $second = app(RecruitmentService::class)->selectCandidate($this->order($client), $this->candidate());
        $payload = ['visa_number' => ' 1912345678 ', 'visa_issued_at' => now()->subDays(5)->toDateString(), 'musaned_contract_no' => 'MS-55', 'contracted_at' => now()->toDateString()];

        $this->actingAs($admin)->post(route('admin.recruitment.contract', $first), $payload)->assertSessionHas('success');
        $this->assertSame('1912345678', $first->fresh()->visa_number);
        $this->assertSame(RecruitmentStatus::Contracted, $first->fresh()->status);

        // Same client, second order: needs another visa number.
        $this->actingAs($admin)->post(route('admin.recruitment.contract', $second), $payload)->assertSessionHasErrors('visa_number');
        $this->actingAs($admin)->post(route('admin.recruitment.contract', $second), [...$payload, 'visa_number' => '1912345679'])->assertSessionHas('success');
    }

    public function test_step_out_of_order_shows_an_error(): void
    {
        $admin = $this->userWithRole('super_admin');
        $order = $this->order($this->client());

        $this->actingAs($admin)->post(route('admin.recruitment.deliver', $order), ['delivered_at' => now()->toDateString(), 'delivery_method' => 'office', 'receiver_name' => 'Fahad'])
            ->assertSessionHas('error');
        $this->assertSame(RecruitmentStatus::New, $order->fresh()->status);
    }

    /**
     * An order whose worker arrived $days days ago, housed in $camp.
     */
    private function arrivedOrder(Client $client, ?Camp $camp = null, int $days = 3): RecruitmentOrder
    {
        $service = app(RecruitmentService::class);
        $date = now()->subDays($days)->toDateString();
        $order = $service->selectCandidate($this->order($client), $this->candidate());
        $order = $service->contract($order, ['visa_number' => (string) random_int(1000000000, 1999999999), 'musaned_contract_no' => 'MS-1', 'contracted_at' => $date]);
        $order = $service->startTravel($order, $date);

        return $service->markArrived($order, $date, $camp);
    }

    public function test_arrival_form_lists_only_the_branch_camps_and_houses_the_worker(): void
    {
        $admin = $this->userWithRole('super_admin');
        $client = $this->client();
        $service = app(RecruitmentService::class);
        $order = $service->startTravel($service->contract($service->selectCandidate($this->order($client), $this->candidate()), [
            'visa_number' => '1911111111', 'musaned_contract_no' => 'MS-1', 'contracted_at' => now()->toDateString(),
        ]));
        $own = Camp::factory()->create(['branch_id' => $client->branch_id, 'name_en' => 'Riyadh Housing']);
        $other = Camp::factory()->create(['branch_id' => $this->branch('QSM')->id, 'name_en' => 'Qassim Housing']);

        $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.recruitment.show', $order))
            ->assertSee('Riyadh Housing')
            ->assertDontSee('Qassim Housing');

        $this->actingAs($admin)->post(route('admin.recruitment.arrived', $order), ['date' => now()->toDateString(), 'camp_id' => $other->id])->assertNotFound();
        $this->actingAs($admin)->post(route('admin.recruitment.arrived', $order), ['date' => now()->toDateString(), 'camp_id' => $own->id])->assertSessionHas('success');

        $this->assertSame($own->id, $order->worker->fresh()->camp_id);
    }

    public function test_delivery_records_the_receiver_and_fees_and_prints_the_handover_report(): void
    {
        Setting::put('camp_free_days', '1');
        Setting::put('camp_daily_fee', '50');
        Setting::put('home_delivery_fee', '120');
        $admin = $this->userWithRole('super_admin');
        $client = $this->client();
        $order = $this->arrivedOrder($client, Camp::factory()->create(['branch_id' => $client->branch_id]), 3);

        $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.recruitment.show', $order))
            ->assertOk()
            ->assertSee('name="receiver_name"', false)
            ->assertSee(route('admin.recruitment.handover', [$order, 'method' => 'office']), false);

        // Draft handover report before delivery.
        $this->actingAs($admin)->get(route('admin.recruitment.handover', [$order, 'method' => 'home']))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($admin)->post(route('admin.recruitment.deliver', $order), [
            'delivered_at' => now()->toDateString(),
            'delivery_method' => 'home',
            'receiver_name' => 'Fahad Al-Dosari',
            'receiver_phone' => '0551234567',
            // Camp fee left empty: the calculated amount (2 days × 50) is used.
            'camp_fee' => '',
            'delivery_fee' => '100',
        ])->assertSessionHas('success');

        $order->refresh();
        $this->assertSame(RecruitmentStatus::Delivered, $order->status);
        $this->assertSame('100.00', $order->camp_fee);
        $this->assertSame('100.00', $order->delivery_fee);
        $this->assertSame($admin->id, $order->delivered_by);
        $this->assertSame(WorkerStatus::InProbation, $order->worker->fresh()->status);
        $this->assertDatabaseHas(AuditLog::class, ['action_key' => 'recruitment.delivered', 'target_id' => $order->id]);

        $this->actingAs($admin)->get(route('admin.recruitment.handover', $order))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_delivery_is_validated(): void
    {
        $admin = $this->userWithRole('super_admin');
        $order = $this->arrivedOrder($this->client());

        $this->actingAs($admin)->post(route('admin.recruitment.deliver', $order), ['delivered_at' => now()->addDay()->toDateString(), 'delivery_method' => 'drone', 'camp_fee' => '-5'])
            ->assertSessionHasErrors(['delivered_at', 'delivery_method', 'receiver_name', 'camp_fee']);
        $this->assertSame(RecruitmentStatus::Arrived, $order->fresh()->status);
    }

    public function test_probation_can_be_completed_or_the_worker_returned(): void
    {
        $admin = $this->userWithRole('super_admin');
        $client = $this->client();
        $service = app(RecruitmentService::class);
        $delivery = ['delivered_at' => now()->toDateString(), 'delivery_method' => 'office', 'receiver_name' => 'Fahad'];
        $kept = $service->deliver($this->arrivedOrder($client), $delivery);
        $returned = $service->deliver($this->arrivedOrder($client), $delivery);
        $reason = Lookup::factory()->create(['type' => LookupType::ReturnReason, 'name_en' => 'Not suitable']);

        $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.recruitment.show', $returned))->assertSee('Not suitable');

        $this->actingAs($admin)->post(route('admin.recruitment.probation.complete', $kept), ['date' => now()->toDateString()])->assertSessionHas('success');
        $this->assertSame(RecruitmentStatus::Completed, $kept->fresh()->status);

        $this->actingAs($admin)->post(route('admin.recruitment.probation.return', $returned), ['date' => now()->toDateString(), 'outcome' => 'returned'])
            ->assertSessionHasErrors('return_reason_id');
        $this->actingAs($admin)->post(route('admin.recruitment.probation.return', $returned), [
            'date' => now()->toDateString(), 'return_reason_id' => $reason->id, 'outcome' => 'returned', 'return_note' => 'Language',
        ])->assertSessionHas('success');

        $this->assertSame(RecruitmentStatus::Returned, $returned->fresh()->status);
        $this->assertSame(WorkerStatus::AwaitingDecision, $returned->worker->fresh()->status);
        $this->assertDatabaseHas(AuditLog::class, ['action_key' => 'recruitment.returned', 'target_id' => $returned->id]);
    }

    public function test_follow_up_page_lists_orders_waiting_for_delivery_and_probations_ending(): void
    {
        $admin = $this->userWithRole('super_admin');
        $client = $this->client();
        $waiting = $this->arrivedOrder($client, Camp::factory()->create(['branch_id' => $client->branch_id]), 5);
        $ending = app(RecruitmentService::class)->deliver($this->arrivedOrder($client, days: Setting::integer('probation_days') + 1), [
            'delivered_at' => now()->subDays(Setting::integer('probation_days'))->toDateString(), 'delivery_method' => 'office', 'receiver_name' => 'Fahad',
        ]);
        $qassim = $this->arrivedOrder($this->client('QSM'));

        $this->actingAs($admin)->get(route('admin.recruitment.follow-up'))
            ->assertOk()
            ->assertSee($waiting->number)
            ->assertSee($ending->number)
            ->assertSee($qassim->number);

        // Branch scope: a Riyadh manager does not see the Qassim order.
        $riyadh = $this->userWithRole('branch_manager', ['RYD']);
        $riyadh->givePermissionTo('recruitment.view');
        $this->actingAs($riyadh)->get(route('admin.recruitment.follow-up'))
            ->assertOk()
            ->assertSee($waiting->number)
            ->assertDontSee($qassim->number);

        $this->actingAs($this->userWithRole('rental_team', ['RYD']))->get(route('admin.recruitment.follow-up'))->assertForbidden();
    }

    public function test_recruitment_settings_are_saved(): void
    {
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->put(route('admin.settings.recruitment'), [
            'home_delivery_fee' => 150, 'camp_free_days' => 2, 'camp_daily_fee' => 60, 'probation_days' => 90,
        ])->assertRedirect(route('admin.settings.edit', ['tab' => 'recruitment']));

        $this->assertSame(150, Setting::integer('home_delivery_fee'));
        $this->assertSame(2, Setting::integer('camp_free_days'));

        $this->actingAs($admin)->put(route('admin.settings.recruitment'), ['home_delivery_fee' => -1])
            ->assertSessionHasErrors(['home_delivery_fee', 'camp_free_days']);
    }

    public function test_candidate_review_needs_permission_and_follows_the_workflow(): void
    {
        $worker = Worker::factory()->create(['branch_id' => $this->branch('RYD')->id]);
        $user = $this->userWithRole('rental_team', ['RYD']);
        $user->givePermissionTo('workers.view');

        $this->actingAs($user)->get(route('admin.workers.show', $worker))->assertDontSee('review-modal');
        $this->actingAs($user)->post(route('admin.workers.review', $worker), ['status' => 'under_review'])->assertForbidden();

        $user->givePermissionTo('candidates.approve');
        $this->actingAs($user)->get(route('admin.workers.show', $worker))->assertSee('review-modal');

        $this->actingAs($user)->post(route('admin.workers.review', $worker), ['status' => 'ready_for_selection'])->assertSessionHasErrors('status');
        $this->actingAs($user)->post(route('admin.workers.review', $worker), ['status' => 'under_review'])->assertRedirect();
        $this->actingAs($user)->post(route('admin.workers.review', $worker), ['status' => 'rejected'])->assertSessionHasErrors('reason');
        $this->actingAs($user)->post(route('admin.workers.review', $worker), ['status' => 'ready_for_selection'])->assertRedirect();

        $this->assertSame(WorkerStatus::ReadyForSelection, $worker->fresh()->status);
    }
}
