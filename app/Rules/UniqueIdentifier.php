<?php

namespace App\Rules;

use App\Support\IdentifierHash;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

/**
 * Rejects a passport / iqama / national ID already stored, comparing the
 * HMAC lookup column (so case and spaces do not matter). Checks every
 * branch: a duplicate in another branch is still a duplicate.
 */
class UniqueIdentifier implements ValidationRule
{
    /**
     * @param  class-string<Model>  $model
     */
    public function __construct(
        private string $model,
        private string $lookupColumn,
        private ?int $ignoreId = null,
        private string $messageKey = 'validation.unique',
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = $this->model::query()
            ->withoutGlobalScopes()
            ->where($this->lookupColumn, IdentifierHash::make((string) $value))
            ->when($this->ignoreId, fn ($query) => $query->whereKeyNot($this->ignoreId))
            ->exists();

        if ($exists) {
            $fail($this->messageKey)->translate();
        }
    }
}
