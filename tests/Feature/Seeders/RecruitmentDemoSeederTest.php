<?php

namespace Tests\Feature\Seeders;

use App\Enums\RecruitmentStatus;
use App\Enums\WorkerStatus;
use App\Models\RecruitmentOrder;
use Database\Seeders\RecruitmentDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecruitmentDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_order_at_every_step_once_and_the_screens_open(): void
    {
        $this->seedAccess();
        $this->seed(RecruitmentDemoSeeder::class);

        $orders = RecruitmentOrder::query()->withoutGlobalScopes()->where('notes', RecruitmentDemoSeeder::MARKER)->get();
        $this->assertSame(
            collect(RecruitmentStatus::cases())->map->value->sort()->values()->all(),
            $orders->pluck('status')->map->value->unique()->sort()->values()->all(),
        );

        $delivered = $orders->firstWhere('status', RecruitmentStatus::Delivered);
        $this->assertSame(WorkerStatus::InProbation, $delivered->worker->status);
        $this->assertNotNull($delivered->receiver_name);
        $arrived = $orders->firstWhere('status', RecruitmentStatus::Arrived);
        $this->assertNotNull($arrived->arrival_camp_id);

        // Running it again adds nothing.
        $this->seed(RecruitmentDemoSeeder::class);
        $this->assertSame($orders->count(), RecruitmentOrder::query()->withoutGlobalScopes()->where('notes', RecruitmentDemoSeeder::MARKER)->count());

        $admin = $this->userWithRole('super_admin');
        $this->actingAs($admin)->get(route('admin.recruitment.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.recruitment.follow-up'))->assertOk()->assertSee($arrived->number);
        $this->actingAs($admin)->get(route('admin.recruitment.show', $delivered))->assertOk();
        $this->actingAs($admin)->get(route('admin.recruitment.handover', $delivered))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }
}
