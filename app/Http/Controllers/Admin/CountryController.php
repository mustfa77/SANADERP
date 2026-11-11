<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Country page with tabs: details, travel stages and document types.
 * The country itself is edited through the reference list (ReferenceController).
 */
class CountryController extends Controller
{
    public function show(Request $request, Country $country): View
    {
        $tab = in_array($request->query('tab'), ['stages', 'documents'], true) ? $request->query('tab') : 'stages';

        return view('admin.reference.countries.show', [
            'country' => $country->loadCount(['agencies', 'authorities']),
            'stages' => $country->travelStages()->get(),
            'documentTypes' => DocumentType::query()->where('country_id', $country->id)->with(['profession', 'authority'])->ordered()->get(),
            'tab' => $tab,
        ]);
    }
}
