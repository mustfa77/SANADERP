<?php

namespace App\Models;

use App\Models\Concerns\HasActiveFlag;
use Database\Factories\CompletedVisaListSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attach the latest N completed visas to the printed file of an authority
 * for a country.
 */
#[Fillable(['authority_id', 'country_id', 'visas_count', 'is_active'])]
class CompletedVisaListSetting extends Model
{
    /** @use HasFactory<CompletedVisaListSettingFactory> */
    use HasActiveFlag, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visas_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Authority, $this>
     */
    public function authority(): BelongsTo
    {
        return $this->belongsTo(Authority::class);
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
