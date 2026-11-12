<?php

namespace App\Services\Documents;

use App\Models\Authority;
use App\Models\Country;

/**
 * Source of the "latest completed visas" list attached to a printed
 * authority file. Bound to NullCompletedVisasProvider until recruitment
 * exists; the recruitment module will bind the real query.
 */
interface CompletedVisasProvider
{
    /**
     * Newest first.
     *
     * @return list<array{visa_number: string, name: string, issued_at: string}>
     */
    public function latest(Authority $authority, Country $country, int $count): array;
}
