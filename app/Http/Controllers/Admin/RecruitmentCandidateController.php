<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WorkerStatus;
use App\Http\Controllers\Controller;
use App\Models\Authority;
use App\Models\Country;
use App\Models\Profession;
use App\Models\Worker;
use App\Services\Recruitment\RecruitmentDocumentProgress;
use App\Services\Recruitment\RecruitmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

/**
 * Recruitment screens built on workers (branch scoped):
 * - ready candidates that can be offered to a client now;
 * - document completeness of every worker in the recruitment pipeline.
 */
class RecruitmentCandidateController extends Controller
{
    /**
     * Pipeline statuses whose documents are followed (rejected excluded).
     *
     * @var list<WorkerStatus>
     */
    private const DOCUMENT_STATUSES = [
        WorkerStatus::CandidateNew, WorkerStatus::UnderReview, WorkerStatus::ReadyForSelection,
        WorkerStatus::OnHold, WorkerStatus::Contracted, WorkerStatus::InTravelProcess, WorkerStatus::Arrived,
    ];

    public function __construct(private RecruitmentDocumentProgress $progress) {}

    public function ready(Request $request, RecruitmentService $recruitment): View
    {
        $filters = $request->only(['search', 'country_id', 'profession_id']);

        $workers = $this->filtered($recruitment->selectableWorkers(), $filters)
            ->with(['country', 'profession', 'agency'])
            ->orderByDesc('id')
            ->paginate(24)
            ->withQueryString();

        $workers->getCollection()->each(fn (Worker $worker) => $worker->setAttribute('document_progress', $this->progress->for($worker)));

        return view('admin.recruitment.ready', [
            'workers' => $workers,
            'filters' => $filters,
            'countries' => Country::query()->ordered()->get(),
            'professions' => Profession::query()->ordered()->get(),
        ]);
    }

    public function documents(Request $request): View
    {
        $filters = $request->only(['search', 'country_id', 'profession_id', 'status', 'completeness']);
        $statuses = array_map(fn (WorkerStatus $status): string => $status->value, self::DOCUMENT_STATUSES);

        $rows = $this->filtered(Worker::query(), $filters)
            ->whereIn('status', filled($filters['status'] ?? null) && in_array($filters['status'], $statuses, true) ? [$filters['status']] : $statuses)
            ->with(['country', 'profession'])
            ->orderBy('name_en')
            ->limit(1000)
            ->get()
            ->each(fn (Worker $worker) => $worker->setAttribute('document_progress', $this->progress->for($worker)));

        $summary = [
            'total' => $rows->count(),
            'complete' => $rows->filter(fn (Worker $worker): bool => $worker->document_progress['complete'])->count(),
        ];
        $summary['incomplete'] = $summary['total'] - $summary['complete'];

        $rows = match ($filters['completeness'] ?? null) {
            'complete' => $rows->filter(fn (Worker $worker): bool => $worker->document_progress['complete']),
            'incomplete' => $rows->reject(fn (Worker $worker): bool => $worker->document_progress['complete']),
            default => $rows,
        };
        // Least complete first.
        $rows = $rows->sortBy(fn (Worker $worker): int => $worker->document_progress['percent'])->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $workers = new LengthAwarePaginator($rows->forPage($page, 25)->values(), $rows->count(), 25, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return view('admin.recruitment.documents', [
            'workers' => $workers,
            'summary' => $summary,
            'filters' => $filters,
            'statuses' => self::DOCUMENT_STATUSES,
            'countries' => Country::query()->ordered()->get(),
            'professions' => Profession::query()->ordered()->get(),
            'authorityCountries' => Authority::query()->active()->distinct()->pluck('country_id')->all(),
        ]);
    }

    /**
     * @param  Builder<Worker>  $query
     * @param  array<string, string|null>  $filters
     * @return Builder<Worker>
     */
    private function filtered(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $id = preg_match('/^W-?0*(\d+)$/i', trim($search), $match) ? (int) $match[1] : null;

                $query->where(fn (Builder $q) => $q
                    ->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('cv_reference_no', 'like', "%{$search}%")
                    ->when($id, fn (Builder $byId) => $byId->orWhere('id', $id)));
            })
            ->when($filters['country_id'] ?? null, fn (Builder $query, string $country) => $query->where('country_id', $country))
            ->when($filters['profession_id'] ?? null, fn (Builder $query, string $profession) => $query->where('profession_id', $profession));
    }
}
