<?php

namespace App\Http\Requests\Admin;

use App\Enums\Gender;
use App\Enums\LookupType;
use App\Enums\MaritalStatus;
use App\Enums\PassportType;
use App\Enums\Religion;
use App\Enums\SponsorType;
use App\Enums\WorkerRentalType;
use App\Models\Worker;
use App\Rules\UniqueIdentifier;
use App\Support\IdentifierHash;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Create / update a worker. Status is never accepted here, except the
 * starting status on create (workers.change_status, workflow.initial).
 */
class WorkerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $worker = $this->route('worker');

        return $worker instanceof Worker
            ? $this->user()->can('update', $worker)
            : $this->user()->can('create', Worker::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'passport_no' => IdentifierHash::normalize($this->input('passport_no')),
            'iqama_no' => IdentifierHash::normalize($this->input('iqama_no')),
            'languages' => array_values(array_filter((array) $this->input('languages', []))),
            'skills' => array_values(array_filter((array) $this->input('skills', []))),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $worker = $this->route('worker');
        $branchIds = $this->user()->allowedBranchIds();

        return [
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'birth_date' => ['nullable', 'date', 'before:today', 'after:1940-01-01'],
            'religion' => ['nullable', Rule::enum(Religion::class)],
            'marital_status' => ['nullable', Rule::enum(MaritalStatus::class)],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],

            'country_id' => ['required', Rule::exists('countries', 'id')->whereNull('deleted_at')],
            'profession_id' => ['required', Rule::exists('professions', 'id')->whereNull('deleted_at')],
            'agency_id' => ['nullable', Rule::exists('agencies', 'id')->where('country_id', $this->input('country_id'))->whereNull('deleted_at')],

            'branch_id' => ['required', Rule::in($branchIds)],
            'camp_id' => ['nullable', Rule::exists('camps', 'id')->where('branch_id', $this->input('branch_id'))->whereNull('deleted_at')],

            'passport_no' => ['required', 'string', 'min:5', 'max:20', 'alpha_num', new UniqueIdentifier(Worker::class, 'passport_lookup', $worker?->id, 'workers.validation.duplicate_passport')],
            'passport_type' => ['required', Rule::enum(PassportType::class)],
            'passport_expiry' => ['nullable', 'date'],
            'iqama_no' => ['nullable', 'string', 'regex:/^2\d{9}$/', new UniqueIdentifier(Worker::class, 'iqama_lookup', $worker?->id, 'workers.validation.duplicate_iqama')],
            'iqama_expiry' => ['nullable', 'date'],
            'medical_expiry' => ['nullable', 'date'],

            'languages' => ['array'],
            'languages.*' => [Rule::exists('lookups', 'id')->where('type', LookupType::Language->value)],
            'skills' => ['array'],
            'skills.*' => [Rule::exists('lookups', 'id')->where('type', LookupType::Skill->value)],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:50'],
            'salary' => ['required', 'numeric', 'min:0', 'max:100000'],
            'salary_change_reason' => ['nullable', 'string', 'max:500'],

            'sponsor_type' => ['required', Rule::enum(SponsorType::class)],
            'rental_type' => ['required', Rule::enum(WorkerRentalType::class)],
            'musaned_cv_no' => ['nullable', 'string', 'max:50'],

            'initial_status' => [
                $worker ? 'prohibited' : 'nullable',
                Rule::in($this->user()->can('workers.change_status') ? config('workflow.initial') : ['candidate_new']),
            ],
        ];
    }

    /**
     * A salary change on an existing worker needs a reason.
     *
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $worker = $this->route('worker');

                if ($worker instanceof Worker
                    && $this->filled('salary')
                    && round((float) $this->input('salary'), 2) !== round((float) $worker->salary, 2)
                    && blank($this->input('salary_change_reason'))) {
                    $validator->errors()->add('salary_change_reason', __('workers.validation.salary_reason_required'));
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return collect(array_keys($this->rules()))
            ->reject(fn (string $field): bool => str_contains($field, '*'))
            ->mapWithKeys(fn (string $field): array => [$field => __("workers.fields.{$field}")])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'iqama_no.regex' => __('workers.validation.iqama_format'),
        ];
    }
}
