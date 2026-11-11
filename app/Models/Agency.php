<?php

namespace App\Models;

use App\Models\Concerns\HasActiveFlag;
use App\Models\Concerns\HasTranslations;
use Database\Factories\AgencyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * External recruitment agency abroad. Agency portal users belong to one.
 * "country" is the legacy 2-letter code; country_id is the reference.
 */
#[Fillable(['name_ar', 'name_en', 'country_id', 'contact_person', 'country', 'email', 'phone', 'is_active'])]
class Agency extends Model
{
    /** @use HasFactory<AgencyFactory> */
    use HasActiveFlag, HasFactory, HasTranslations, SoftDeletes;

    /**
     * Keep the legacy 2-letter code in step with the selected country.
     */
    protected static function booted(): void
    {
        static::saving(function (self $agency): void {
            if ($agency->isDirty('country_id') && $agency->country_id) {
                $agency->country = Country::query()->whereKey($agency->country_id)->value('code') ?? $agency->country;
            }
        });
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function originCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Candidate, $this>
     */
    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function isInUse(): bool
    {
        return $this->users()->exists() || $this->candidates()->exists();
    }
}
