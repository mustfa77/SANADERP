<?php

namespace App\Models;

use App\Enums\CampType;
use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\HasActiveFlag;
use App\Models\Concerns\HasTranslations;
use Database\Factories\CampFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Worker housing, owned by a branch (BranchScope applies).
 */
#[Fillable([
    'branch_id', 'name_ar', 'name_en', 'district_ar', 'district_en', 'address_ar', 'address_en',
    'capacity', 'type', 'is_active',
])]
class Camp extends Model
{
    /** @use HasFactory<CampFactory> */
    use BelongsToBranch, HasActiveFlag, HasFactory, HasTranslations, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'type' => CampType::class,
        ];
    }

    /**
     * Camp movements do not exist yet, so nothing references a camp.
     */
    public function isInUse(): bool
    {
        return false;
    }
}
