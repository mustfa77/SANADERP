<?php

namespace Tests;

use App\Models\Branch;
use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Seed branches (Riyadh RYD, Qassim QSM), permissions and default roles.
     */
    protected function seedAccess(): void
    {
        $this->seed([BranchSeeder::class, PermissionSeeder::class, RoleSeeder::class]);
    }

    /**
     * Active internal user with the given role and branch codes.
     *
     * @param  list<string>  $branchCodes
     */
    protected function userWithRole(string $role, array $branchCodes = [], array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole($role);
        $user->branches()->sync(Branch::query()->whereIn('code', $branchCodes)->pluck('id'));

        return $user;
    }

    protected function branch(string $code): Branch
    {
        return Branch::query()->where('code', $code)->firstOrFail();
    }
}
