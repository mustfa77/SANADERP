<?php

namespace App\Support;

use RuntimeException;

/**
 * Normalisation and HMAC-SHA256 lookup hash for sensitive identifiers
 * (passport, iqama, national ID). The values themselves are stored
 * encrypted; uniqueness and exact search use only the *_lookup column.
 */
class IdentifierHash
{
    /**
     * Trim, drop all whitespace and upper-case ("  p01 23 4c" -> "P01234C").
     */
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = mb_strtoupper((string) preg_replace('/\s+/u', '', $value));

        return $normalized === '' ? null : $normalized;
    }

    /**
     * HMAC of the normalised value with IDENTIFIER_HASH_KEY, or null when empty.
     */
    public static function make(?string $value): ?string
    {
        $normalized = self::normalize($value);

        return $normalized === null ? null : hash_hmac('sha256', $normalized, self::key());
    }

    private static function key(): string
    {
        $key = (string) config('app.identifier_hash_key');

        if ($key === '') {
            throw new RuntimeException('IDENTIFIER_HASH_KEY is not set.');
        }

        return $key;
    }
}
