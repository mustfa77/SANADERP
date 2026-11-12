<?php

namespace App\Models;

use App\Enums\AlertType;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * In-system alert (branch scoped). Written only by App\Services\Alerts\AlertService.
 */
class Alert extends Model
{
    use BelongsToBranch;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AlertType::class,
            'due_at' => 'date',
            'params' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }

    /**
     * Only the alert types whose subject the user may see.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $types = array_values(array_filter(AlertType::cases(), fn (AlertType $type): bool => $user->can($type->permission())));

        return $query->whereIn('type', $types);
    }

    /**
     * Message in the current language (bilingual params pick their locale).
     */
    public function message(): string
    {
        $locale = app()->getLocale();
        $params = collect($this->params ?? [])->map(fn ($value) => is_array($value) ? ($value[$locale] ?? $value['ar'] ?? reset($value)) : $value)->all();

        return __('alerts.messages.'.$this->type->value, $params);
    }

    /**
     * Page of the alert's subject.
     */
    public function url(): ?string
    {
        $subject = $this->subject;

        return match (true) {
            $subject instanceof Worker => route('admin.workers.show', $subject),
            $subject instanceof RentalContract => route('admin.rental.show', $subject),
            $subject instanceof RecruitmentOrder => route('admin.recruitment.show', $subject),
            $subject instanceof Trip => route('admin.trips.index', ['date' => $subject->scheduled_at->toDateString()]),
            $subject instanceof DriverProfile => route('admin.drivers.index'),
            $subject instanceof WorkerCase => route('admin.cases.show', $subject),
            default => null,
        };
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo()->withoutGlobalScopes();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
