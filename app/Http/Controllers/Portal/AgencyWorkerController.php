<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Scopes\BranchScope;
use App\Models\Worker;
use App\Services\Workers\WorkerCvPdf;
use App\Services\Workers\WorkerCvService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * External agency portal: the agency's own workers and their CV. An agency
 * never sees another agency's workers (another worker id is a 404), nor
 * passport numbers, clients or prices.
 */
class AgencyWorkerController extends Controller
{
    public function __construct(private WorkerCvService $cv) {}

    public function index(Request $request): View
    {
        $workers = $this->agencyWorkers($request)
            ->with(['country', 'profession'])
            ->when($request->query('search'), fn (Builder $query, string $search) => $query->where(fn (Builder $q) => $q
                ->where('name_en', 'like', "%{$search}%")
                ->orWhere('name_ar', 'like', "%{$search}%")
                ->orWhere('cv_reference_no', 'like', "%{$search}%")))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('portal.agency.workers', ['workers' => $workers]);
    }

    public function edit(Request $request, int $worker): View
    {
        return view('portal.agency.worker-cv', ['worker' => $this->find($request, $worker)->load(['profession', 'experiences'])]);
    }

    public function update(Request $request, int $worker): RedirectResponse
    {
        $model = $this->find($request, $worker)->load('profession');
        $data = $request->validate($this->cv->rules($model), [], $this->cv->attributes($model));
        $this->cv->update($model, $data, $request->user(), $request->file('photo'), $request->file('full_photo'));

        return redirect()->route('agency.workers.edit', $model->id)->with('success', __('cv.flash.updated'));
    }

    /**
     * CV page (opens in a new tab) with print and download buttons.
     */
    public function preview(Request $request, int $worker, WorkerCvPdf $pdf): View
    {
        $model = $this->find($request, $worker);

        return $pdf->preview($model, [
            'photo' => route('agency.workers.photo', [$model->id, 'face']),
            'full' => route('agency.workers.photo', [$model->id, 'full']),
            'download' => route('agency.workers.pdf', $model->id),
        ]);
    }

    public function pdf(Request $request, int $worker, WorkerCvPdf $pdf): Response
    {
        return $pdf->download($this->find($request, $worker));
    }

    public function photo(Request $request, int $worker, string $type): StreamedResponse
    {
        $model = $this->find($request, $worker);
        $path = $type === 'full' ? $model->full_photo_path : $model->photo_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['X-Content-Type-Options' => 'nosniff']);
    }

    /**
     * Workers of the user's agency (branch scope does not apply: agencies
     * have no branches).
     *
     * @return Builder<Worker>
     */
    private function agencyWorkers(Request $request): Builder
    {
        return Worker::query()
            ->withoutGlobalScope(BranchScope::class)
            ->where('agency_id', $request->user()->agency_id ?? 0);
    }

    private function find(Request $request, int $id): Worker
    {
        return $this->agencyWorkers($request)->findOrFail($id);
    }
}
