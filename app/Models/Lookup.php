<?php

namespace App\Models;

use App\Enums\LookupType;
use App\Models\Concerns\HasActiveFlag;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\Sortable;
use Database\Factories\LookupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Generic lookup list item (return / cancel reasons, payment methods).
 */
#[Fillable(['type', 'name_ar', 'name_en', 'sort_order', 'is_active'])]
class Lookup extends Model
{
    /** @use HasFactory<LookupFactory> */
    use HasActiveFlag, HasFactory, HasTranslations, SoftDeletes, Sortable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LookupType::class,
        ];
    }

    /**
     * @return list<string>
     */
    public function sortGroupColumns(): array
    {
        return ['type'];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOfType(Builder $query, LookupType $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Contracts and payments do not exist yet, so nothing references an item.
     */
    public function isInUse(): bool
    {
        return false;
    }
}
