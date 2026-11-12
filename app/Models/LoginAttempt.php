<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'email', 'successful', 'failure_reason', 'ip_address', 'user_agent'])]
class LoginAttempt extends Model
{
    use Prunable;

    public const UPDATED_AT = null;

    /**
     * Attempts older than the retention setting (0 days = keep forever).
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        $days = Setting::integer('login_attempts_retention_days');

        return $days > 0
            ? static::query()->where('created_at', '<', now()->subDays($days))
            : static::query()->whereRaw('1 = 0');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'successful' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
