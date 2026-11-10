<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Database\Factories\TripFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['branch_id', 'driver_id', 'scheduled_at', 'status'])]
class Trip extends Model
{
    /** @use HasFactory<TripFactory> */
    use BelongsToBranch, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }
}
