<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * "is_active" flag. Inactive records stay in the database (and keep their
 * history) but are not offered for new use.
 */
trait HasActiveFlag
{
    public function initializeHasActiveFlag(): void
    {
        $this->mergeCasts(['is_active' => 'boolean']);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('is_active'), true);
    }
}
