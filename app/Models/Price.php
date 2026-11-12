<?php

namespace App\Models;

use App\Enums\RentalType;
use App\Models\Concerns\HasActiveFlag;
use Database\Factories\PriceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Rental price (before VAT) per country and rental type. Only one active
 * price per country + type; older ones stay inactive as history.
 */
#[Fillable(['country_id', 'rental_type', 'price', 'vat_percent', 'is_active'])]
class Price extends Model
{
    /** @use HasFactory<PriceFactory> */
    use HasActiveFlag, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rental_type' => RentalType::class,
            'price' => 'decimal:2',
            'vat_percent' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function vatAmount(): float
    {
        return round((float) $this->price * (float) $this->vat_percent / 100, 2);
    }

    public function totalWithVat(): float
    {
        return round((float) $this->price + $this->vatAmount(), 2);
    }
}
