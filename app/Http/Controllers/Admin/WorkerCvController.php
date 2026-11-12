<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Worker;
use App\Services\Workers\WorkerCvPdf;
use App\Services\Workers\WorkerCvService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Worker CV for staff (the external agency fills the same form from its portal).
 */
class WorkerCvController extends Controller
{
    public function __construct(private WorkerCvService $cv) {}

    public function edit(Worker $worker): View
    {
        return view('admin.workers.cv-edit', ['worker' => $worker->load(['profession', 'experiences'])]);
    }

    public function update(Request $request, Worker $worker): RedirectResponse
    {
        $worker->load('profession');
        $data = $request->validate($this->cv->rules($worker), [], $this->cv->attributes($worker));
        $this->cv->update($worker, $data, $request->user(), $request->file('photo'), $request->file('full_photo'));

        return redirect()->route('admin.workers.show', [$worker, 'tab' => 'cv'])->with('success', __('cv.flash.updated'));
    }

    /**
     * CV page (opens in a new tab) with print and download buttons.
     */
    public function preview(Worker $worker, WorkerCvPdf $pdf): View
    {
        return $pdf->preview($worker, [
            'photo' => route('admin.workers.photo', $worker),
            'full' => route('admin.workers.full-photo', $worker),
            'download' => route('admin.workers.cv.pdf', $worker),
        ]);
    }

    /**
     * CV PDF, downloaded directly.
     */
    public function pdf(Worker $worker, WorkerCvPdf $pdf): Response
    {
        return $pdf->download($worker);
    }

    /**
     * Full-body photo from the private disk.
     */
    public function fullPhoto(Worker $worker): StreamedResponse
    {
        abort_unless($worker->full_photo_path && Storage::disk('local')->exists($worker->full_photo_path), 404);

        return Storage::disk('local')->response($worker->full_photo_path, null, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, max-age=3600']);
    }
}
