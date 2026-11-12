<?php

namespace App\Http\Requests\Admin;

use App\Models\Client;
use App\Models\User;
use App\Rules\UniqueIdentifier;
use App\Support\IdentifierHash;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create / update a client. National ID: Saudi ID (1...) or iqama (2...).
 * The rules are shared with the "new client" section of the recruitment
 * order screen (clientRules / clientMessages / clientAttributes with a prefix).
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
        $this->merge(self::normalize($this->all()));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return self::clientRules($this->user(), $this->route('client'));
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return self::clientAttributes();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return self::clientMessages();
    }

    /**
     * Normalised national ID and e-mail.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalize(array $input): array
    {
        return [
            'national_id' => IdentifierHash::normalize($input['national_id'] ?? null),
            'email' => filled($input['email'] ?? null) ? strtolower(trim((string) $input['email'])) : null,
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function clientRules(User $user, ?Client $client = null, string $prefix = ''): array
    {
        $rules = [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'national_id' => ['required', 'regex:/^[12]\d{9}$/', new UniqueIdentifier(Client::class, 'national_id_lookup', $client?->id, 'clients.validation.duplicate_national_id')],
            'phone' => ['required', 'regex:/^05\d{8}$/'],
            'phone2' => ['nullable', 'regex:/^05\d{8}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'national_address' => ['nullable', 'string', 'max:500'],
            'branch_id' => ['required', Rule::in($user->allowedBranchIds())],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ];

        return collect($rules)->mapWithKeys(fn (array $rule, string $field): array => [$prefix.$field => $rule])->all();
    }

    /**
     * @return array<string, string>
     */
    public static function clientAttributes(string $prefix = ''): array
    {
        return collect(['name_ar', 'name_en', 'national_id', 'phone', 'phone2', 'email', 'city', 'district', 'national_address', 'branch_id', 'notes', 'is_active'])
            ->mapWithKeys(fn (string $field): array => [$prefix.$field => __("clients.fields.{$field}")])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function clientMessages(string $prefix = ''): array
    {
        return [
            $prefix.'national_id.regex' => __('clients.validation.national_id_format'),
            $prefix.'phone.regex' => __('validation.custom.phone.regex'),
            $prefix.'phone2.regex' => __('validation.custom.phone.regex'),
        ];
    }
}
