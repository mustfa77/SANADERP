<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Spatie role with bilingual name / description. "name" stays the unique
 * machine key (e.g. rental_team).
 */
#[Fillable([
    'name', 'guard_name', 'name_ar', 'name_en', 'description_ar', 'description_en', 'is_system', 'requires_2fa',
])]
class Role extends SpatieRole
{
    use HasTranslations;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'requires_2fa' => 'boolean',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->name === config('permissions.super_admin_role');
    }

    public function displayName(): string
    {
        return (string) ($this->trans('name') ?: $this->name);
    }
}
