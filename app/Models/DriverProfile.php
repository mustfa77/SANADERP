<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\HasActiveFlag;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vehicle and licence of a driver (a user of type "driver"). Branch scoped.
 */
#[Fillable(['branch_id', 'license_number', 'license_expiry', 'vehicle_model', 'vehicle_plate', 'seats', 'is_active'])]
class DriverProfile extends Model
{
    use BelongsToBranch, HasActiveFlag;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'license_expiry' => 'date',
            'seats' => 'integer',
            'is_active' => 'boolean',
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
