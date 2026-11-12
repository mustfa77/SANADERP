<?php

namespace App\Services\Clients;

use App\Models\Client;
use App\Models\User;
use App\Services\AuditLogger;

/**
 * Creates a client and audits it (client screen and the quick "new client"
 * section of the recruitment order screen). Validation is done by the
 * caller with ClientRequest::clientRules().
 */
class ClientRegistrar
{
    /**
     * Audited fields; the national ID is never written in clear.
     */
    public const AUDITED_FIELDS = [
        'name_ar', 'name_en', 'phone', 'phone2', 'email', 'city', 'district', 'national_address', 'branch_id', 'notes', 'is_active',
    ];

    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function register(array $data, User $creator): Client
    {
        $client = new Client(['is_active' => true, ...$data]);
        $client->created_by = $creator->id;
        $client->save();

        $this->audit->log('client.created', $client, ['name' => $client->translations('name')], new: self::auditable($client));

        return $client;
    }

    /**
     * @return array<string, mixed>
     */
    public static function auditable(Client $client): array
    {
        return [
            ...array_map(fn ($value) => is_bool($value) ? (int) $value : $value, $client->only(self::AUDITED_FIELDS)),
            'national_id' => $client->national_id_lookup ? substr($client->national_id_lookup, 0, 8) : null,
        ];
    }
}
