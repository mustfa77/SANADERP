<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\CountryTravelStage;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Travel stages of a country, managed from the country page.
 */
class TravelStageController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function create(Country $country): View
    {
        return view('admin.reference.countries.stage-form', ['country' => $country, 'stage' => new CountryTravelStage(['is_active' => true])]);
    }

    public function store(Request $request, Country $country): RedirectResponse
    {
        $stage = $country->travelStages()->create($this->validated($request));
        $this->audit->log('stage.created', $stage, $this->params($country, $stage), new: $stage->only(['name_ar', 'name_en', 'max_days', 'is_active']));

        return $this->backToCountry($country, 'reference.flash.created');
    }

    public function edit(Country $country, CountryTravelStage $stage): View
    {
        return view('admin.reference.countries.stage-form', ['country' => $country, 'stage' => $stage]);
    }

    public function update(Request $request, Country $country, CountryTravelStage $stage): RedirectResponse
    {
        $old = $stage->only(['name_ar', 'name_en', 'max_days', 'is_active']);
        $stage->update($this->validated($request));
        $changes = array_intersect_key($stage->getChanges(), $old);

        if ($changes !== []) {
            $this->audit->log('stage.updated', $stage, $this->params($country, $stage), array_intersect_key($old, $changes), $changes);
        }

        return $this->backToCountry($country, 'reference.flash.updated');
    }

    public function move(Request $request, Country $country, CountryTravelStage $stage): RedirectResponse
    {
        $stage->move($request->input('direction') === 'up' ? -1 : 1);

        return redirect()->route('admin.countries.show', [$country, 'tab' => 'stages']);
    }

    public function toggle(Country $country, CountryTravelStage $stage): RedirectResponse
    {
        $stage->update(['is_active' => ! $stage->is_active]);
        $this->audit->log(
            $stage->is_active ? 'stage.activated' : 'stage.deactivated',
            $stage,
            $this->params($country, $stage),
            ['is_active' => ! $stage->is_active],
            ['is_active' => $stage->is_active],
        );

        return $this->backToCountry($country, $stage->is_active ? 'reference.flash.activated' : 'reference.flash.deactivated');
    }

    /**
     * Stages are not referenced yet (recruitment tracking comes later).
     */
    public function destroy(Country $country, CountryTravelStage $stage): RedirectResponse
    {
        $stage->delete();
        $this->audit->log('stage.deleted', $stage, $this->params($country, $stage), $stage->only(['name_ar', 'name_en', 'max_days']));

        return $this->backToCountry($country, 'reference.flash.deleted');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'max_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'is_active' => ['nullable', 'boolean'],
        ], [], [
            'name_ar' => __('reference.field_in_language.ar', ['field' => __('reference.fields.name')]),
            'name_en' => __('reference.field_in_language.en', ['field' => __('reference.fields.name')]),
            'max_days' => __('reference.fields.max_days'),
        ]);

        return [...$validated, 'is_active' => $request->boolean('is_active')];
    }

    /**
     * @return array<string, mixed>
     */
    private function params(Country $country, CountryTravelStage $stage): array
    {
        return ['name' => $stage->translations('name'), 'country' => $country->translations('name')];
    }

    private function backToCountry(Country $country, string $message): RedirectResponse
    {
        return redirect()->route('admin.countries.show', [$country, 'tab' => 'stages'])
            ->with('success', __($message, ['item' => __('reference.resources.stages.singular')]));
    }
}
