<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One audit entry. The message is stored as a translation key
 * (lang/{ar,en}/audit.php "actions.{action_key}") plus parameters, so it is
 * rendered in the viewer's language. A parameter given as ['ar' => .., 'en' => ..]
 * is resolved to the current locale.
 */
#[Fillable([
    'user_id', 'action_key', 'params', 'target_type', 'target_id', 'old_values', 'new_values',
    'ip_address', 'user_agent',
])]
class AuditLog extends Model
{
    use Prunable;

    public const UPDATED_AT = null;

    /**
     * Entries older than the retention setting (0 days = keep forever).
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        $days = Setting::integer('audit_retention_days');

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
            'params' => 'array',
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    public function description(): string
    {
        $locale = app()->getLocale();
        $params = collect($this->params ?? [])
            ->map(fn (mixed $value): string => is_array($value)
                ? (string) (filled($value[$locale] ?? null) ? $value[$locale] : collect($value)->filter()->first())
                : (string) $value)
            ->all();

        return __("audit.actions.{$this->action_key}", $params);
    }

    public function actionLabel(): string
    {
        return __("audit.action_names.{$this->action_key}");
    }

    public function targetTypeLabel(): ?string
    {
        return $this->target_type ? __('audit.targets.'.class_basename($this->target_type)) : null;
    }
}
