<?php

namespace App\Models;

use App\Models\Concerns\HasActiveFlag;
use App\Models\Concerns\HasTranslations;
use Database\Factories\AuthorityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * External authority (e.g. a labor office) that receives printed files.
 */
#[Fillable(['country_id', 'name_ar', 'name_en', 'city_ar', 'city_en', 'address_ar', 'address_en', 'is_active'])]
class Authority extends Model
{
    /** @use HasFactory<AuthorityFactory> */
    use HasActiveFlag, HasFactory, HasTranslations, SoftDeletes;

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return HasMany<DocumentType, $this>
     */
    public function documentTypes(): HasMany
    {
        return $this->hasMany(DocumentType::class);
    }

    /**
     * @return HasMany<CompletedVisaListSetting, $this>
     */
    public function completedVisaListSettings(): HasMany
    {
        return $this->hasMany(CompletedVisaListSetting::class);
    }

    /**
     * @return HasMany<DocumentPrint, $this>
     */
    public function prints(): HasMany
    {
        return $this->hasMany(DocumentPrint::class);
    }

    public function isInUse(): bool
    {
        return $this->documentTypes()->exists() || $this->prints()->exists();
    }
}
