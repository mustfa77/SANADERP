<?php

namespace App\Http\Requests\Admin;

use App\Models\Client;
use App\Models\RecruitmentOrder;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * New recruitment order, in one screen:
 * - an existing client (visible to the user, active), or a new client
 *   entered in the same form (needs clients.create; same rules as the
 *   client screen, fields under "client[...]");
 * - optionally the worker already shown to the client (checked by
 *   RecruitmentService::selectCandidate).
 */
class RecruitmentOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->can('create', RecruitmentOrder::class)) {
            return false;
        }

        return ! $this->isNewClient() || $this->user()->can('create', Client::class);
    }

    public function isNewClient(): bool
    {
        return $this->input('client_mode') === 'new';
    }

    protected function prepareForValidation(): void
    {
        if ($this->isNewClient()) {
            $this->merge(['client' => [...(array) $this->input('client', []), ...ClientRequest::normalize((array) $this->input('client', []))]]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'client_mode' => ['required', Rule::in(['existing', 'new'])],
            'country_id' => ['required', Rule::exists('countries', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'profession_id' => ['required', Rule::exists('professions', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'worker_id' => ['nullable', 'integer'],
            'contract_amount' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        if ($this->isNewClient()) {
            return [...$rules, ...ClientRequest::clientRules($this->user(), null, 'client.')];
        }

        return [...$rules, 'client_id' => ['required', 'integer', function (string $attribute, mixed $value, Closure $fail): void {
            if (! Client::query()->active()->whereKey($value)->exists()) {
                $fail(__('validation.exists', ['attribute' => __('recruitment.fields.client_id')]));
            }
        }]];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            ...collect(['client_id', 'country_id', 'profession_id', 'worker_id', 'contract_amount', 'notes'])
                ->mapWithKeys(fn (string $field): array => [$field => __("recruitment.fields.{$field}")])->all(),
            ...ClientRequest::clientAttributes('client.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ClientRequest::clientMessages('client.');
    }

    /**
     * Validated client data for a new client (without the "client." prefix).
     *
     * @return array<string, mixed>
     */
    public function newClientData(): array
    {
        return (array) $this->validated('client', []);
    }
}
