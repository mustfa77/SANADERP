<?php

namespace App\Services\Recruitment;

use RuntimeException;

/**
 * A recruitment step that cannot be done now (translated message).
 */
class RecruitmentException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $replace
     */
    public static function because(string $key, array $replace = []): self
    {
        return new self(__("recruitment.errors.{$key}", $replace));
    }
}
