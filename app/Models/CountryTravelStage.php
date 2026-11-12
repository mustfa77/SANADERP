<?php

namespace App\Models;

use App\Models\Concerns\HasActiveFlag;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\Sortable;
use Database\Factories\CountryTravelStageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One step of the travel process for a country, in sort_order.
 */
#[Fillable(['country_id', 'name_ar', 'name_en', 'sort_order', 'max_days', 'is_active'])]
class CountryTravelStage extends Model
{
    /** @use HasFactory<CountryTravelStageFactory> */
    use HasActiveFlag, HasFactory, HasTranslations, SoftDeletes, Sortable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_days' => 'integer',
        ];
    }

    /**
     * @return list<string>
     */
    public function sortGroupColumns(): array
    {
        return ['country_id'];
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
