<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CaseDecision;
use App\Enums\CaseStatus;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\WorkerCase;
use App\Services\Cases\CaseException;
use App\Services\Cases\WorkerCaseService;
use App\Services\Workers\InvalidStatusTransition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Cases of workers waiting for a decision (branch scoped). Every step goes
 * through WorkerCaseService.
 */
class WorkerCaseController extends Controller
{
    public function __construct(private WorkerCaseService $cases) {}

    public function index(Request $request): View
    {
        $status = CaseStatus::tryFrom((string) $request->query('status', 'open'));
        $search = $request->query('search');

        return view('admin.cases.index', [
            'cases' => WorkerCase::query()
                ->when($status, fn (Builder $query) => $query->where('status', $status))
                ->when($search, fn (Builder $query, string $value) => $query->where(fn (Builder $q) => $q
                    ->where('number', 'like', "%{$value}%")
                    ->orWhereHas('worker', fn (Builder $worker) => $worker->where('name_ar', 'like', "%{$value}%")->orWhere('name_en', 'like', "%{$value}%"))))
                ->with(['worker.country', 'decider'])
                ->orderBy('opened_at')
                ->paginate(25)
                ->withQueryString(),
            'status' => $status,
            'search' => $search,
            'counts' => WorkerCase::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(WorkerCase $case): View
    {
        $case->load(['worker.country', 'worker.profession', 'worker.camp', 'related', 'newSponsor', 'notes.author', 'opener', 'decider', 'completer']);

        return view('admin.cases.show', [
            'case' => $case,
            'decisions' => CaseDecision::cases(),
            'clients' => $case->status === CaseStatus::Open ? Client::query()->active()->orderBy('name_ar')->get() : collect(),
            'history' => $case->worker->statusHistory()->limit(10)->get(),
        ]);
    }

    public function decide(Request $request, WorkerCase $case): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', Rule::enum(CaseDecision::class)],
            'decision_note' => ['nullable', 'string', 'max:1000'],
            'new_sponsor_client_id' => ['nullable', 'integer', Rule::exists('clients', 'id')->whereIn('branch_id', $request->user()->allowedBranchIds())],
            'new_sponsor_name' => ['nullable', 'string', 'max:255'],
            'new_sponsor_id_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'transfer_fee' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'flight_date' => ['nullable', 'date'],
            'ticket_cost' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ], [], $this->attributes());

        return $this->attempt(fn () => $this->cases->decide($case, CaseDecision::from($validated['decision']), $validated), $case, 'cases.flash.decided');
    }

    public function complete(Request $request, WorkerCase $case): RedirectResponse
    {
        $validated = $request->validate([
            'completed_at' => ['required', 'date', 'before_or_equal:today'],
            'flight_date' => ['nullable', 'date'],
            'ticket_cost' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'transfer_fee' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'note' => ['nullable', 'string', 'max:2000'],
        ], [], $this->attributes());

        return $this->attempt(fn () => $this->cases->complete($case, $validated), $case, 'cases.flash.completed');
    }

    public function note(Request $request, WorkerCase $case): RedirectResponse
    {
        $validated = $request->validate(['note' => ['required', 'string', 'max:2000']], [], ['note' => __('cases.fields.note')]);
        $this->cases->addNote($case, $validated['note']);

        return redirect()->route('admin.cases.show', $case)->with('success', __('cases.flash.note'));
    }

    /**
     * @param  callable(): mixed  $step
     */
    private function attempt(callable $step, WorkerCase $case, string $message): RedirectResponse
    {
        try {
            $step();
        } catch (CaseException|InvalidStatusTransition $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.cases.show', $case)->with('success', __($message));
    }

    /**
     * @return array<string, string>
     */
    private function attributes(): array
    {
        return collect(['decision', 'decision_note', 'new_sponsor_client_id', 'new_sponsor_name', 'new_sponsor_id_number', 'transfer_fee', 'flight_date', 'ticket_cost', 'completed_at', 'note'])
            ->mapWithKeys(fn (string $field): array => [$field => __("cases.fields.{$field}")])->all();
    }
}
