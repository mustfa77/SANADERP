<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Spatie permission with bilingual labels. Permissions are only created by
 * the PermissionSeeder from config/permissions.php, never from the UI.
 */
#[Fillable(['name', 'guard_name', 'label_ar', 'label_en', 'group', 'sort_order'])]
class Permission extends SpatiePermission
{
    use HasTranslations;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
