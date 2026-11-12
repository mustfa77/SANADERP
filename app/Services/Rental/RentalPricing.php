<?php

namespace App\Services\Rental;

use App\Enums\RentalType;
use App\Models\Price;

/**
 * Prices from Settings > Prices (active price per country and rental type)
 * and the VAT / total calculation shared by contracts and bookings.
 */
class RentalPricing
{
    public function activePrice(?int $countryId, RentalType $type): ?Price
    {
        if (! $countryId) {
            return null;
        }

        return Price::query()->active()->where('country_id', $countryId)->where('rental_type', $type)->latest('id')->first();
    }

    /**
     * Countries that have an active price for the rental type (hourly rental
     * is only offered for these countries).
     *
     * @return list<int>
     */
    public function countriesWithPrice(RentalType $type): array
    {
        return Price::query()->active()->where('rental_type', $type)->distinct()->pluck('country_id')->map(fn ($id): int => (int) $id)->all();
    }

    /**
     * VAT is charged on the price after the discount.
     *
     * @return array{price: float, discount: float, vat_percent: float, vat_amount: float, total: float}
     */
    public function amounts(float $price, float $discount, float $vatPercent): array
    {
        if ($discount < 0 || $discount > $price) {
            throw RentalException::because('discount_too_high');
        }

        $net = $price - $discount;
        $vat = round($net * $vatPercent / 100, 2);

        return [
            'price' => round($price, 2),
            'discount' => round($discount, 2),
            'vat_percent' => $vatPercent,
            'vat_amount' => $vat,
            'total' => round($net + $vat, 2),
        ];
    }
}
