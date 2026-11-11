<?php

namespace App\Models;

use App\Models\Concerns\HasActiveFlag;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\Sortable;
use Database\Factories\CountryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['code', 'name_ar', 'name_en', 'nationality_ar', 'nationality_en', 'sort_order', 'is_active'])]
class Country extends Model
{
    /** @use HasFactory<CountryFactory> */
    use HasActiveFlag, HasFactory, HasTranslations, SoftDeletes, Sortable;

    /**
     * @return HasMany<CountryTravelStage, $this>
     */
    public function travelStages(): HasMany
    {
        return $this->hasMany(CountryTravelStage::class)->ordered();
    }

    /**
     * Used by scoped route binding (countries/{country}/stages/{stage}).
     *
     * @return HasMany<CountryTravelStage, $this>
     */
    public function stages(): HasMany
    {
        return $this->travelStages();
    }

    /**
     * Document types specific to this country (global ones have no country).
     *
     * @return HasMany<DocumentType, $this>
     */
    public function documentTypes(): HasMany
    {
        return $this->hasMany(DocumentType::class)->ordered();
    }

    /**
     * @return HasMany<Agency, $this>
     */
    public function agencies(): HasMany
    {
        return $this->hasMany(Agency::class);
    }

    /**
     * @return HasMany<Authority, $this>
     */
    public function authorities(): HasMany
    {
        return $this->hasMany(Authority::class);
    }

    /**
     * @return HasMany<Price, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }

    /**
     * Records that would be orphaned by deleting this country.
     */
    public function isInUse(): bool
    {
        return $this->agencies()->exists()
            || $this->authorities()->exists()
            || $this->prices()->exists()
            || DocumentType::query()->where('country_id', $this->getKey())->exists();
    }
}
