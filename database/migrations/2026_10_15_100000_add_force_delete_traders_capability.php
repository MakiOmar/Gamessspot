<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const ABILITY = 'force-delete-traders';

    /**
     * Roles that could already force delete (via manage-traders) keep that access.
     */
    public function up(): void
    {
        Role::query()->each(function (Role $role) {
            $capabilities = $role->capabilities ?? array();

            if (! in_array('manage-traders', $capabilities, true) || in_array(self::ABILITY, $capabilities, true)) {
                return;
            }

            $role->capabilities = array_values(array_merge($capabilities, array(self::ABILITY)));
            $role->save();
        });
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
