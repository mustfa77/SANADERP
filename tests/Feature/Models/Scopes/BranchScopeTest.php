<?php

namespace Tests\Feature\Models\Scopes;

use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BranchScopeTest extends TestCase
{
    use RefreshDatabase;

    private Worker $riyadhWorker;

    private Worker $qassimWorker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();

        // No workers module exists yet: a minimal route with model binding
        // stands in for its "show" page.
        Route::middleware(['web', 'panel'])->get('test/workers/{worker}', fn (Worker $worker) => $worker->name_en);

        $this->riyadhWorker = Worker::factory()->create(['branch_id' => $this->branch('RYD')->id, 'name_en' => 'Riyadh Worker']);
        $this->qassimWorker = Worker::factory()->create(['branch_id' => $this->branch('QSM')->id, 'name_en' => 'Qassim Worker']);
    }

    public function test_riyadh_user_gets_404_on_qassim_record_by_url(): void
    {
        $user = $this->userWithRole('branch_manager', ['RYD']);

        $this->actingAs($user)->get('test/workers/'.$this->riyadhWorker->id)->assertOk()->assertSee('Riyadh Worker');
        $this->actingAs($user)->get('test/workers/'.$this->qassimWorker->id)->assertNotFound();
    }

    public function test_queries_only_return_the_users_branches(): void
    {
        $this->actingAs($this->userWithRole('branch_manager', ['RYD']));

        $this->assertSame([$this->riyadhWorker->id], Worker::query()->pluck('id')->all());
    }

    public function test_user_with_all_branches_permission_sees_every_branch(): void
    {
        $this->actingAs($this->userWithRole('general_manager'));

        $this->assertCount(2, Worker::query()->get());
    }

    public function test_selected_branch_in_switcher_limits_results_to_that_branch(): void
    {
        $user = $this->userWithRole('general_manager');

        $this->actingAs($user)
            ->post(route('admin.branch.switch'), ['branch_id' => $this->branch('QSM')->id])
            ->assertSessionHas(User::SELECTED_BRANCH_SESSION_KEY, $this->branch('QSM')->id);

        $this->get('test/workers/'.$this->riyadhWorker->id)->assertNotFound();
        $this->get('test/workers/'.$this->qassimWorker->id)->assertOk();
    }

    public function test_switcher_rejects_a_branch_the_user_cannot_access(): void
    {
        $user = $this->userWithRole('branch_manager', ['RYD']);

        $this->actingAs($user)
            ->post(route('admin.branch.switch'), ['branch_id' => $this->branch('QSM')->id])
            ->assertSessionHasErrors('branch_id');
    }
}
