<?php

use App\Models\Role;
use App\Services\RolePermissionService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $abilities = array(
        'view-traders',
        'manage-traders',
        'void-trader-transactions',
    );

    public function up(): void
    {
        $matrix = app(RolePermissionService::class)->defaultMatrix();

        foreach ($matrix as $roleName => $capabilities) {
            $toAdd = array_values(array_intersect($this->abilities, $capabilities));

            if ($toAdd === array()) {
                continue;
            }

            $role = Role::where('name', $roleName)->first();

            if ($role === null) {
                continue;
            }

            $role->capabilities = array_values(array_unique(array_merge($role->capabilities, $toAdd)));
            $role->save();
        }
    }

    public function down(): void
    {
        Role::query()->each(function (Role $role) {
            $remaining = array_values(array_diff($role->capabilities, $this->abilities));

            if (count($remaining) === count($role->capabilities)) {
                return;
            }

            $role->capabilities = $remaining;
            $role->save();
        });
    }
};
