<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Authority;
use App\Models\Candidate;
use App\Models\Country;
use App\Models\Profession;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Development-only page (registered when APP_ENV=local) to try the
 * documents engine on a demo candidate before workers exist.
 */
class DocumentsDemoController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless(app()->isLocal(), 404);

        $countries = Country::query()->active()->ordered()->get();
        $country = $countries->firstWhere('id', $request->integer('country')) ?? $countries->first();
        $profession = Profession::query()->find($request->integer('profession'));
        $authorities = Authority::query()->active()->when($country, fn ($query) => $query->where('country_id', $country->id))->get();
        $authority = $authorities->firstWhere('id', $request->integer('authority')) ?? $authorities->first();

        $agency = Agency::query()->firstOrCreate(
            ['name_en' => 'Demo agency (documents)'],
            ['name_ar' => 'مكتب تجريبي (المستندات)', 'country_id' => $country?->id, 'is_active' => true],
        );
        $owner = Candidate::query()->firstOrCreate(
            ['name_en' => 'Demo candidate (documents)'],
            ['name_ar' => 'مرشحة تجريبية (المستندات)', 'agency_id' => $agency->id, 'status' => 'new'],
        );

        if ($country && $owner->nationality !== $country->code) {
            $owner->update(['nationality' => $country->code]);
        }

        return view('admin.documents-demo', [
            'owner' => $owner,
            'countries' => $countries,
            'country' => $country,
            'professions' => Profession::query()->active()->ordered()->get(),
            'profession' => $profession,
            'authorities' => $authorities,
            'authority' => $authority,
        ]);
    }
}
