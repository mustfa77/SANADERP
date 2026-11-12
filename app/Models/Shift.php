<?php

namespace App\Models;

use App\Models\Concerns\HasActiveFlag;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\Sortable;
use Database\Factories\ShiftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Hourly-rental shift. start_time / move_time are stored as "HH:MM:SS".
 */
#[Fillable(['name_ar', 'name_en', 'start_time', 'move_time', 'hours', 'sort_order', 'is_active'])]
class Shift extends Model
{
    /** @use HasFactory<ShiftFactory> */
    use HasActiveFlag, HasFactory, HasTranslations, SoftDeletes, Sortable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hours' => 'decimal:2',
        ];
    }

    /**
     * In use once an hourly booking was made with it.
     */
    public function isInUse(): bool
    {
        return HourlyBooking::query()->withoutGlobalScopes()->where('shift_id', $this->getKey())->exists();
    }
}
