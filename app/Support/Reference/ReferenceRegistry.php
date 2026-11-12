<?php

namespace App\Support\Reference;

use App\Enums\CampType;
use App\Enums\LookupType;
use App\Enums\RentalType;
use App\Models\Agency;
use App\Models\Authority;
use App\Models\Branch;
use App\Models\Camp;
use App\Models\CompletedVisaListSetting;
use App\Models\Country;
use App\Models\Lookup;
use App\Models\Price;
use App\Models\Profession;
use App\Models\Shift;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * All reference-data lists under Settings. Routes, the sidebar and
 * ReferenceController are generated from these definitions.
 */
class ReferenceRegistry
{
    /**
     * @var array<string, ReferenceResource>|null
     */
    private ?array $resources = null;

    /**
     * @return array<string, ReferenceResource>
     */
    public function all(): array
    {
        return $this->resources ??= $this->define();
    }

    public function get(string $key): ReferenceResource
    {
        return $this->all()[$key] ?? throw new InvalidArgumentException("Unknown reference resource [{$key}].");
    }

    /**
     * @return array<string, ReferenceResource>
     */
    private function define(): array
    {
        $settings = ['view' => 'settings.view', 'create' => 'settings.manage', 'edit' => 'settings.manage', 'delete' => 'settings.manage'];
        $module = fn (string $module): array => [
            'view' => "{$module}.view", 'create' => "{$module}.create", 'edit' => "{$module}.edit", 'delete' => "{$module}.delete",
        ];
        $name = ['type' => 'text', 'translatable' => true, 'required' => true, 'list' => true];

        $resources = [
            new ReferenceResource(
                key: 'countries',
                model: Country::class,
                icon: 'ri-earth-line',
                permissions: $settings,
                fields: [
                    'name' => $name,
                    'nationality' => ['type' => 'text', 'translatable' => true, 'required' => true, 'list' => true],
                    'code' => [
                        'type' => 'text', 'list' => true, 'dir' => 'ltr',
                        'rules' => fn (?Model $record): array => ['size:2', 'alpha', Rule::unique('countries', 'code')->ignore($record)],
                    ],
                ],
                search: ['code', 'name_ar', 'name_en', 'nationality_ar', 'nationality_en'],
                showRoute: 'admin.countries.show',
            ),
            new ReferenceResource(
                key: 'agencies',
                model: Agency::class,
                icon: 'ri-building-2-line',
                permissions: $module('agencies'),
                fields: [
                    'name' => $name,
                    'country_id' => ['type' => 'select', 'required' => true, 'list' => true, 'options' => $this->countryOptions()],
                    'contact_person' => ['type' => 'text', 'list' => true],
                    'phone' => ['type' => 'tel', 'list' => true, 'dir' => 'ltr'],
                    'email' => ['type' => 'email', 'dir' => 'ltr'],
                ],
                search: ['name_ar', 'name_en', 'contact_person', 'phone', 'email'],
                filters: ['country_id' => $this->countryOptions()],
            ),
            new ReferenceResource(
                key: 'authorities',
                model: Authority::class,
                icon: 'ri-government-line',
                permissions: $module('authorities'),
                fields: [
                    'name' => $name,
                    'country_id' => ['type' => 'select', 'required' => true, 'list' => true, 'options' => $this->countryOptions()],
                    'city' => ['type' => 'text', 'translatable' => true, 'list' => true],
                    'address' => ['type' => 'textarea', 'translatable' => true],
                ],
                search: ['name_ar', 'name_en', 'city_ar', 'city_en'],
                filters: ['country_id' => $this->countryOptions()],
            ),
            new ReferenceResource(
                key: 'camps',
                model: Camp::class,
                icon: 'ri-home-4-line',
                permissions: $module('camps'),
                fields: [
                    'name' => $name,
                    'branch_id' => ['type' => 'select', 'required' => true, 'list' => true, 'options' => $this->branchOptions()],
                    'type' => ['type' => 'select', 'required' => true, 'list' => true, 'options' => $this->enumOptions(CampType::class)],
                    'capacity' => ['type' => 'number', 'required' => true, 'list' => true, 'rules' => ['max:10000']],
                    'district' => ['type' => 'text', 'translatable' => true, 'list' => true],
                    'address' => ['type' => 'textarea', 'translatable' => true],
                ],
                search: ['name_ar', 'name_en', 'district_ar', 'district_en'],
                filters: ['branch_id' => $this->branchOptions(), 'type' => $this->enumOptions(CampType::class)],
            ),
            new ReferenceResource(
                key: 'professions',
                model: Profession::class,
                icon: 'ri-briefcase-line',
                permissions: $settings,
                fields: [
                    'name' => $name,
                    'cv_template' => [
                        'type' => 'select', 'required' => true, 'list' => true,
                        'options' => fn (): array => collect(array_keys(config('cv_templates')))
                            ->mapWithKeys(fn (string $key): array => [$key => __("cv.templates.{$key}")])->all(),
                    ],
                ],
                search: ['name_ar', 'name_en'],
            ),
            new ReferenceResource(
                key: 'shifts',
                model: Shift::class,
                icon: 'ri-time-line',
                permissions: $settings,
                fields: [
                    'name' => $name,
                    'start_time' => ['type' => 'time', 'required' => true, 'list' => true, 'dir' => 'ltr'],
                    'move_time' => ['type' => 'time', 'list' => true, 'dir' => 'ltr'],
                    'hours' => ['type' => 'decimal', 'required' => true, 'list' => true, 'step' => '0.5', 'rules' => ['gt:0', 'max:24']],
                ],
                search: ['name_ar', 'name_en'],
            ),
            new ReferenceResource(
                key: 'prices',
                model: Price::class,
                icon: 'ri-price-tag-3-line',
                permissions: $settings,
                fields: [
                    'country_id' => ['type' => 'select', 'required' => true, 'list' => true, 'options' => $this->countryOptions()],
                    'rental_type' => ['type' => 'select', 'required' => true, 'list' => true, 'options' => $this->enumOptions(RentalType::class)],
                    'price' => ['type' => 'decimal', 'required' => true, 'list' => true, 'step' => '0.01', 'rules' => ['max:1000000']],
                    'vat_percent' => ['type' => 'decimal', 'required' => true, 'list' => true, 'step' => '0.01', 'rules' => ['max:100']],
                ],
                filters: ['country_id' => $this->countryOptions(), 'rental_type' => $this->enumOptions(RentalType::class)],
                extraRules: $this->singleActivePriceRule(),
            ),
            new ReferenceResource(
                key: 'lookups',
                model: Lookup::class,
                icon: 'ri-list-check-2',
                permissions: $settings,
                fields: [
                    'type' => ['type' => 'select', 'required' => true, 'list' => true, 'options' => $this->enumOptions(LookupType::class)],
                    'name' => $name,
                ],
                search: ['name_ar', 'name_en'],
                filters: ['type' => $this->enumOptions(LookupType::class)],
            ),
            new ReferenceResource(
                key: 'completed_visa_lists',
                model: CompletedVisaListSetting::class,
                icon: 'ri-file-list-3-line',
                permissions: ['view' => 'settings.view', 'create' => 'document_types.manage', 'edit' => 'document_types.manage', 'delete' => 'document_types.manage'],
                fields: [
                    'authority_id' => ['type' => 'select', 'required' => true, 'list' => true, 'options' => $this->authorityOptions()],
                    'country_id' => [
                        'type' => 'select', 'required' => true, 'list' => true, 'options' => $this->countryOptions(),
                        'rules' => fn (?Model $record): array => [
                            Rule::unique('completed_visa_list_settings', 'country_id')->where('authority_id', request('authority_id'))->ignore($record),
                        ],
                    ],
                    'visas_count' => ['type' => 'number', 'required' => true, 'list' => true, 'rules' => ['min:1', 'max:500']],
                ],
                filters: ['authority_id' => $this->authorityOptions(), 'country_id' => $this->countryOptions()],
            ),
        ];

        return collect($resources)->keyBy('key')->all();
    }

    /**
     * Active countries plus, for edits, the record's current one is still
     * accepted by the select rule because inactive ones stay listed last.
     *
     * @return Closure(): array<int, string>
     */
    private function countryOptions(): Closure
    {
        return fn (): array => Country::query()->ordered()->get()
            ->mapWithKeys(fn (Country $country): array => [$country->id => $country->trans('name').($country->is_active ? '' : ' ('.__('common.inactive').')')])
            ->all();
    }

    /**
     * @return Closure(): array<int, string>
     */
    private function authorityOptions(): Closure
    {
        return fn (): array => Authority::query()->orderBy('name_'.app()->getLocale())->get()
            ->mapWithKeys(fn (Authority $authority): array => [$authority->id => $authority->trans('name')])
            ->all();
    }

    /**
     * Only the branches the user may access.
     *
     * @return Closure(): array<int, string>
     */
    private function branchOptions(): Closure
    {
        return fn (): array => Branch::query()
            ->whereKey(Auth::user()?->allowedBranchIds() ?? [])
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (Branch $branch): array => [$branch->id => $branch->trans('name')])
            ->all();
    }

    /**
     * @param  class-string<CampType|LookupType|RentalType>  $enum
     * @return Closure(): array<string, string>
     */
    private function enumOptions(string $enum): Closure
    {
        return fn (): array => collect($enum::cases())->mapWithKeys(fn ($case): array => [$case->value => $case->label()])->all();
    }

    /**
     * Only one active price per country and rental type.
     *
     * @return Closure(array<string, mixed>, ?Model): array<string, mixed>
     */
    private function singleActivePriceRule(): Closure
    {
        return fn (array $input, ?Model $record): array => [
            'is_active' => [function (string $attribute, mixed $value, Closure $fail) use ($input, $record): void {
                if (! filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
                    return;
                }

                $exists = Price::query()
                    ->where('country_id', $input['country_id'] ?? null)
                    ->where('rental_type', $input['rental_type'] ?? null)
                    ->where('is_active', true)
                    ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                    ->exists();

                if ($exists) {
                    $fail(__('reference.resources.prices.duplicate_active'));
                }
            }],
        ];
    }
}
