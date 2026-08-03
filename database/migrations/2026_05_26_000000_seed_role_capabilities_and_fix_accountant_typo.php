<?php

use App\Services\RolePermissionService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::table('roles')->where('name', 'accountatnt')->exists()) {
            DB::table('roles')
                ->where('name', 'accountatnt')
                ->update(array(
                    'name' => 'accountant',
                    'updated_at' => now(),
                ));
        }

        $service = app(RolePermissionService::class);
        $service->seedStaffRoleCapabilities();
        $service->seedCustomerCapabilities();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('roles')
            ->whereNotIn('name', config('permissions.excluded_roles', array('customer')))
            ->update(array(
                'capabilities' => json_encode(array()),
                'updated_at' => now(),
            ));
    }
};
