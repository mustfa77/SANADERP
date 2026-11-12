<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Models\Scopes\BranchScope;
use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'code', 'name_ar', 'name_en', 'city_ar', 'city_en', 'address_ar', 'address_en',
    'phone', 'email', 'manager_id', 'is_active',
])]
class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_branches')->withTimestamps();
    }

    /**
     * Not scoped by the current branch: counts belong to this branch.
     *
     * @return HasMany<Worker, $this>
     */
    public function workers(): HasMany
    {
        return $this->hasMany(Worker::class)->withoutGlobalScope(BranchScope::class);
    }

    /**
     * @return HasMany<Trip, $this>
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class)->withoutGlobalScope(BranchScope::class);
    }
}
