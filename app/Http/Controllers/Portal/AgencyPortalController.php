<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * External agency portal: an agency only sees its own candidates and
 * documents (never clients, prices or other agencies).
 */
class AgencyPortalController extends Controller
{
    public function candidates(Request $request): View
    {
        $candidates = Candidate::query()
            ->where('agency_id', $request->user()->agency_id)
            ->latest()
            ->paginate(20);

        return view('portal.agency.candidates', ['candidates' => $candidates]);
    }

    public function documents(Request $request): View
    {
        $documents = Document::query()
            ->with('candidate')
            ->where('agency_id', $request->user()->agency_id)
            ->latest()
            ->paginate(20);

        return view('portal.agency.documents', ['documents' => $documents]);
    }
}
