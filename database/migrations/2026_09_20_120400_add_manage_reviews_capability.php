<?php

use App\Models\Role;
use App\Services\RolePermissionService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $ability = 'manage-reviews';
        $matrix = app(RolePermissionService::class)->defaultMatrix();

        foreach ($matrix as $roleName => $capabilities) {
            if (! in_array($ability, $capabilities, true)) {
                continue;
            }

            $role = Role::where('name', $roleName)->first();

            if ($role === null || $role->hasCapability($ability)) {
                continue;
            }

            $updated = $role->capabilities;
            $updated[] = $ability;
            $role->capabilities = $updated;
            $role->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $ability = 'manage-reviews';

        Role::query()->each(function (Role $role) use ($ability) {
            if (! $role->hasCapability($ability)) {
                return;
            }

            $role->capabilities = array_values(array_diff($role->capabilities, array($ability)));
            $role->save();
        });
    }
};
