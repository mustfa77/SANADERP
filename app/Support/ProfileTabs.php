<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Reads config/profile_tabs.php: the tabs of a profile page the user may
 * see, in sort order, and which one is active.
 */
class ProfileTabs
{
    /**
     * @return Collection<int, array{key: string, label: string, view: string, permission: string, icon: string, sort: int}>
     */
    public static function for(string $profile, User $user): Collection
    {
        return collect(config("profile_tabs.{$profile}", []))
            ->filter(fn (array $tab): bool => $user->can($tab['permission']))
            ->sortBy('sort')
            ->values();
    }

    /**
     * The requested tab if visible, otherwise the first visible one.
     *
     * @param  Collection<int, array{key: string}>  $tabs
     */
    public static function active(Collection $tabs, ?string $requested): ?string
    {
        return $tabs->firstWhere('key', $requested)['key'] ?? $tabs->first()['key'] ?? null;
    }
}
