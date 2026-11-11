<?php

namespace App\Services\Documents;

use App\Models\Authority;
use App\Models\Country;

/**
 * Placeholder until recruitment tables exist: there are no completed visas.
 */
class NullCompletedVisasProvider implements CompletedVisasProvider
{
    public function latest(Authority $authority, Country $country, int $count): array
    {
        return [];
    }
}
