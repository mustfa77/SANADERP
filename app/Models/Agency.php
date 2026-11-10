<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\AgencyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name_ar', 'name_en', 'country', 'email', 'phone', 'is_active'])]
class Agency extends Model
{
    /** @use HasFactory<AgencyFactory> */
    use HasFactory, HasTranslations, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
