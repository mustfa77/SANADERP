<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Simple key / value application settings (e.g. session_timeout_minutes).
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    private const CACHE_KEY = 'settings.all';

    /**
     * Security settings editable from the settings page, with their default
     * value and allowed range.
     *
     * @var array<string, array{default: int, min: int, max: int}>
     */
    public const SECURITY = [
        'session_timeout_minutes' => ['default' => 120, 'min' => 5, 'max' => 1440],
        'login_max_attempts' => ['default' => 5, 'min' => 3, 'max' => 20],
        'login_lockout_minutes' => ['default' => 15, 'min' => 1, 'max' => 1440],
        'two_factor_code_minutes' => ['default' => 10, 'min' => 1, 'max' => 60],
    ];

    /**
     * Backup / retention settings (0 days = keep forever).
     *
     * @var array<string, array{default: int, min: int, max: int}>
     */
    public const MAINTENANCE = [
        'backup_hour' => ['default' => 2, 'min' => 0, 'max' => 23],
        'backup_keep' => ['default' => 7, 'min' => 1, 'max' => 90],
        'audit_retention_days' => ['default' => 0, 'min' => 0, 'max' => 3650],
        'login_attempts_retention_days' => ['default' => 90, 'min' => 0, 'max' => 3650],
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever(self::CACHE_KEY, fn (): array => static::query()->pluck('value', 'key')->all());

        return $all[$key] ?? $default;
    }

    /**
     * Read an integer setting, falling back to its default.
     */
    public static function integer(string $key): int
    {
        $definitions = self::SECURITY + self::MAINTENANCE;

        return (int) static::get($key, $definitions[$key]['default'] ?? 0);
    }

    public static function boolean(string $key): bool
    {
        return (bool) static::get($key, false);
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::CACHE_KEY);
    }
}
