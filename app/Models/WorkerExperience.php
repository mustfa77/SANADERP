<?php

namespace App\Models;

use Database\Factories\WorkerExperienceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One previous job in a worker's CV. Short form: country (or city),
 * position, duration. Detailed form: position, employer, location, dates
 * and job description.
 */
#[Fillable(['worker_id', 'country', 'position', 'duration', 'company', 'location', 'started_at', 'ended_at', 'description', 'sort_order'])]
class WorkerExperience extends Model
{
    /** @use HasFactory<WorkerExperienceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'ended_at' => 'date',
        ];
    }

    /**
     * Period for display: "2019-03 → 2022-05" (detailed) or the free-text duration.
     */
    public function period(): string
    {
        if ($this->started_at) {
            return $this->started_at->format('Y-m').' → '.($this->ended_at?->format('Y-m') ?? __('cv.experience.present'));
        }

        return (string) $this->duration;
    }

    /**
     * @return BelongsTo<Worker, $this>
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }
}
