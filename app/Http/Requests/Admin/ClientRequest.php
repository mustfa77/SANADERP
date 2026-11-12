<?php

namespace App\Http\Requests\Admin;

use App\Models\Client;
use App\Rules\UniqueIdentifier;
use App\Support\IdentifierHash;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create / update a client. National ID: Saudi ID (1...) or iqama (2...).
 */
class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->route('client');

        return $client instanceof Client
            ? $this->user()->can('update', $client)
            : $this->user()->can('create', Client::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'national_id' => IdentifierHash::normalize($this->input('national_id')),
            'email' => $this->filled('email') ? strtolower(trim((string) $this->input('email'))) : null,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $client = $this->route('client');

        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'national_id' => ['required', 'regex:/^[12]\d{9}$/', new UniqueIdentifier(Client::class, 'national_id_lookup', $client?->id, 'clients.validation.duplicate_national_id')],
            'phone' => ['required', 'regex:/^05\d{8}$/'],
            'phone2' => ['nullable', 'regex:/^05\d{8}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'national_address' => ['nullable', 'string', 'max:500'],
            'branch_id' => ['required', Rule::in($this->user()->allowedBranchIds())],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return collect(array_keys($this->rules()))->mapWithKeys(fn (string $field): array => [$field => __("clients.fields.{$field}")])->all();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'national_id.regex' => __('clients.validation.national_id_format'),
            'phone.regex' => __('validation.custom.phone.regex'),
            'phone2.regex' => __('validation.custom.phone.regex'),
        ];
    }
}
