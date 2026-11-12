<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Masks sensitive identifiers (passport, iqama, national id, address) for
 * users without the matching "*.view_sensitive" permission. Used by the
 * <x-masked> Blade component and by exports / prints.
 */
class SensitiveData
{
    /**
     * Characters kept visible at the start and the end, per value type.
     *
     * @var array<string, array{0: int, 1: int}>
     */
    private const VISIBLE = [
        'passport' => [3, 2],   // P01****6C
        'iqama' => [2, 2],      // 23******91
        'national_id' => [2, 2], // 10******98
        'address' => [4, 0],
    ];

    /**
     * Value as the current user may see it.
     */
    public function display(?string $value, string $permission, string $type, ?User $user = null): ?string
    {
        $user ??= Auth::user();

        if ($value === null || $value === '' || $user?->can($permission)) {
            return $value;
        }

        return $this->mask($value, $type);
    }

    public function mask(string $value, string $type): string
    {
        [$start, $end] = self::VISIBLE[$type] ?? [2, 2];
        $length = mb_strlen($value);

        if ($length <= $start + $end) {
            return str_repeat('*', $length);
        }

        return mb_substr($value, 0, $start)
            .str_repeat('*', $length - $start - $end)
            .($end > 0 ? mb_substr($value, -$end) : '');
    }
}
