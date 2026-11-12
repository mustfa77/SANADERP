<?php

namespace App\Services\Rental;

use RuntimeException;

/**
 * A rental / booking / payment step that cannot be done now (translated message).
 */
class RentalException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $replace
     */
    public static function because(string $key, array $replace = []): self
    {
        return new self(__("rental.errors.{$key}", $replace));
    }
}
