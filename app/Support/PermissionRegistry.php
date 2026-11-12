<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Reads the permission catalogue in config/permissions.php. Used by the
 * PermissionSeeder, the role permission matrix and the sidebar so they all
 * share one definition.
 */
class PermissionRegistry
{
    /**
     * Every permission defined in the catalogue, in display order.
     *
     * @return list<array{name: string, group: string, module: string, action: string, sort_order: int}>
     */
    public function all(): array
    {
        $permissions = [];
        $sortOrder = 0;

        foreach ($this->sections() as $section => $modules) {
            foreach ($modules as $module => $actions) {
                foreach ($actions as $action) {
                    $permissions[] = [
                        'name' => "{$module}.{$action}",
                        'group' => $section,
                        'module' => $module,
                        'action' => $action,
                        'sort_order' => ++$sortOrder,
                    ];
                }
            }
        }

        return $permissions;
    }

    /**
     * Sections with their modules and each module's full action list
     * (standard or overridden actions followed by special permissions).
     *
     * @return array<string, array<string, list<string>>>
     */
    public function sections(): array
    {
        $standard = config('permissions.standard_actions');
        $special = $this->specialActionsByModule();
        $sections = [];

        foreach (config('permissions.sections') as $section => $modules) {
            foreach ($modules as $module => $actions) {
                $sections[$section][$module] = array_values(array_unique([
                    ...($actions ?? $standard),
                    ...($special[$module] ?? []),
                ]));
            }
        }

        return $sections;
    }

    /**
     * Permission names only.
     *
     * @return list<string>
     */
    public function names(): array
    {
        return array_column($this->all(), 'name');
    }

    /**
     * Expand wildcard patterns ("*", "candidates.*", "*.view") into permission
     * names, then drop any name matching an "except" pattern.
     *
     * @param  list<string>  $patterns
     * @param  list<string>  $except
     * @return list<string>
     */
    public function resolve(array $patterns, array $except = []): array
    {
        return array_values(array_filter(
            $this->names(),
            fn (string $name): bool => Str::is($patterns, $name) && ! Str::is($except, $name),
        ));
    }

    /**
     * Human label of a permission in the given locale.
     */
    public function label(string $name, ?string $locale = null): string
    {
        $specialKey = "permissions.special.{$name}";

        if (in_array($name, config('permissions.special'), true)) {
            return __($specialKey, [], $locale);
        }

        [$module, $action] = explode('.', $name, 2);

        return __('permissions.label', [
            'action' => __("permissions.actions.{$action}", [], $locale),
            'module' => __("permissions.modules.{$module}", [], $locale),
        ], $locale);
    }

    /**
     * @return array<string, list<string>>
     */
    private function specialActionsByModule(): array
    {
        $byModule = [];

        foreach (config('permissions.special') as $name) {
            [$module, $action] = explode('.', $name, 2);
            $byModule[$module][] = $action;
        }

        return $byModule;
    }
}
