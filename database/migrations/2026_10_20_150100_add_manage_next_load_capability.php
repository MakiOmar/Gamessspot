<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const ABILITY = 'manage-next-load';

    /**
     * Admins get the supplier screens by default; other roles are granted from Roles & Permissions.
     */
    public function up(): void
    {
        $role = Role::where('name', 'admin')->first();
        if (! $role) {
            return;
        }

        $capabilities = $role->capabilities ?? array();
        if (! in_array(self::ABILITY, $capabilities, true)) {
            $role->capabilities = array_values(array_merge($capabilities, array(self::ABILITY)));
            $role->save();
        }
    }

    public function down(): void
    {
        Role::query()->each(function (Role $role) {
            $capabilities = $role->capabilities ?? array();
            if (! in_array(self::ABILITY, $capabilities, true)) {
                return;
            }

            $role->capabilities = array_values(array_diff($capabilities, array(self::ABILITY)));
            $role->save();
        });
    }
};
