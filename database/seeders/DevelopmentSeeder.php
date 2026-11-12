<?php

namespace Database\Seeders;

use App\Enums\SponsorType;
use App\Enums\WorkerStatus;
use App\Models\Agency;
use App\Models\Branch;
use App\Models\Camp;
use App\Models\Client;
use App\Models\Country;
use App\Models\DocumentType;
use App\Models\Worker;
use App\Services\Documents\DocumentChecklistService;
use App\Services\Workers\WorkerStatusService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Sample data for development ONLY (refuses to run outside local / testing):
 * 30 workers covering every status, country, branch and both sponsor types,
 * and 10 clients with documents. Not called by DatabaseSeeder.
 *
 *   php artisan db:seed --class=DevelopmentSeeder
 *
 * Statuses are reached through WorkerStatusService along a valid path, so
 * the status history looks like real use.
 */
class DevelopmentSeeder extends Seeder
{
    private const WORKERS = 30;

    private const CLIENTS = 10;

    public function run(WorkerStatusService $statuses, DocumentChecklistService $documents): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('DevelopmentSeeder must never run in production.');
        }

        $this->call([BranchSeeder::class, CountrySeeder::class, ProfessionSeeder::class, LookupSeeder::class]);

        $countries = Country::query()->active()->get();
        $branches = Branch::query()->where('is_active', true)->get();
        $targets = WorkerStatus::cases();

        for ($i = 0; $i < self::WORKERS; $i++) {
            $target = $targets[$i % count($targets)];
            $country = $countries[$i % $countries->count()];
            $branch = $branches[$i % $branches->count()];
            $sponsor = $this->sponsorFor($target, $i);

            $worker = Worker::factory()
                ->state(['country_id' => $country->id, 'branch_id' => $branch->id, 'sponsor_type' => $sponsor])
                ->when($target->group() !== 'recruitment', fn ($factory) => $factory->withIqama())
                ->create([
                    'agency_id' => Agency::query()->where('country_id', $country->id)->inRandomOrder()->value('id'),
                    'camp_id' => $sponsor === SponsorType::Company ? Camp::query()->withoutGlobalScopes()->where('branch_id', $branch->id)->value('id') : null,
                ]);

            $statuses->initialize($worker, WorkerStatus::CandidateNew, __('workers.flash.created'));

            foreach ($this->pathTo($target, $sponsor) as $step) {
                $statuses->transition($worker, $step, 'Development seed');
            }
        }

        $this->seedClients($documents);
    }

    private function seedClients(DocumentChecklistService $documents): void
    {
        $types = collect([
            ['صورة الهوية الوطنية', 'National ID copy', true],
            ['كرت العائلة', 'Family card', false],
            ['إثبات العنوان', 'Proof of address', false],
        ])->map(fn (array $type): DocumentType => DocumentType::query()->firstOrCreate(
            ['applies_to' => 'client', 'name_en' => $type[1]],
            ['name_ar' => $type[0], 'is_required' => $type[2]],
        ));

        Client::factory()->count(self::CLIENTS)->create()->each(function (Client $client) use ($documents, $types): void {
            foreach ($types->take(random_int(1, $types->count())) as $type) {
                $documents->upload($client, $type->id, UploadedFile::fake()->image(str($type->name_en)->slug().'.jpg', 600, 400));
            }
        });
    }

    /**
     * Sponsorship that can reach the target status.
     */
    private function sponsorFor(WorkerStatus $target, int $index): SponsorType
    {
        return match ($target->group()) {
            'client_sponsorship' => SponsorType::Client,
            'company_sponsorship', 'special' => SponsorType::Company,
            default => $index % 2 === 0 ? SponsorType::Company : SponsorType::Client,
        };
    }

    /**
     * Shortest allowed path from candidate_new to the target (breadth-first
     * over config/workflow.php, honouring the sponsor-type conditions).
     *
     * @return list<WorkerStatus>
     */
    private function pathTo(WorkerStatus $target, SponsorType $sponsor): array
    {
        $start = WorkerStatus::CandidateNew->value;
        $queue = [[$start]];
        $seen = [$start => true];

        while ($queue !== []) {
            $path = array_shift($queue);
            $current = end($path);

            if ($current === $target->value) {
                return array_map(fn (string $value): WorkerStatus => WorkerStatus::from($value), array_slice($path, 1));
            }

            foreach (config("workflow.transitions.{$current}", []) as $next) {
                $condition = config("workflow.conditions.{$current}.{$next}.sponsor_type");

                if (isset($seen[$next]) || ($condition !== null && $condition !== $sponsor->value)) {
                    continue;
                }

                $seen[$next] = true;
                $queue[] = [...$path, $next];
            }
        }

        throw new RuntimeException("No workflow path to {$target->value}.");
    }
}
