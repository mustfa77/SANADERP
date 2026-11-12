<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\RecruitmentOrderStageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One travel stage of a recruitment order (copied from the country's stages).
 */
#[Fillable([
    'recruitment_order_id', 'country_travel_stage_id', 'name_ar', 'name_en', 'sort_order',
    'max_days', 'started_at', 'due_at', 'completed_at', 'notes', 'completed_by',
])]
class RecruitmentOrderStage extends Model
{
    /** @use HasFactory<RecruitmentOrderStageFactory> */
    use HasFactory, HasTranslations;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_days' => 'integer',
            'started_at' => 'date',
            'due_at' => 'date',
            'completed_at' => 'date',
        ];
    }

    public function isOverdue(): bool
    {
        return $this->completed_at === null && $this->due_at !== null && $this->due_at->isPast() && ! $this->due_at->isToday();
    }

    /**
     * @return BelongsTo<RecruitmentOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(RecruitmentOrder::class, 'recruitment_order_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
