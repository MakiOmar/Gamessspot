<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class RolePermissionService
{
    /**
     * Default role => capability keys map (mirrors legacy AuthServiceProvider logic).
     *
     * @return array<string, array<int, string>>
     */
    public function defaultMatrix(): array
    {
        return array(
            'admin' => array(
                'access-dashboard',
                'manage-games',
                'edit-games',
                'manage-gift-cards',
                'manage-categories',
                'view-sell-log',
                'manage-sell-log',
                'undo-orders',
                'manage-accounts',
                'manage-options',
                'view-reports',
                'create-order-reports',
                'manage-users',
                'manage-store-profiles',
                'manage-device-repairs',
                'delete-device-repairs',
                'submit-device-request',
                'track-device-status',
            ),
            'sales' => array(
                'access-dashboard',
                'manage-games',
                'manage-gift-cards',
                'view-sell-log',
                'manage-sell-log',
                'create-order-reports',
                'manage-device-repairs',
                'submit-device-request',
                'track-device-status',
            ),
            'account manager' => array(
                'access-dashboard',
                'manage-games',
                'view-sell-log',
                'manage-sell-log',
                'manage-accounts',
                'undo-orders',
                'view-reports',
                'create-order-reports',
                'manage-device-repairs',
            ),
            'accountant' => array(
                'access-dashboard',
                'view-sell-log',
                'manage-sell-log',
                'view-reports',
                'create-order-reports',
                'manage-store-profiles',
                'manage-device-repairs',
            ),
            'call center' => array(
                'access-dashboard',
                'view-sell-log',
            ),
        );
    }

    /**
     * Customer role capabilities (not editable in staff UI; required for dynamic gates).
     *
     * @return array<int, string>
     */
    public function customerCapabilities(): array
    {
        return array(
            'submit-device-request',
            'track-device-status',
        );
    }

    /**
     * @return Collection<int, Role>
     */
    public function staffRoles(): Collection
    {
        return Role::query()
            ->whereNotIn('name', config('permissions.excluded_roles', array('customer')))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    public function allAbilityKeys(): array
    {
        return array_keys(config('permissions.abilities', array()));
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function groupedAbilities(): array
    {
        $grouped = array();

        foreach (config('permissions.abilities', array()) as $key => $meta) {
            $group = $meta['group'] ?? 'Other';
            $grouped[$group][] = array(
                'key' => $key,
                'label' => $meta['label'] ?? $key,
                'group' => $group,
            );
        }

        return $grouped;
    }

    /**
     * @return array{role: array<string, mixed>, permissions: array<int, array<string, mixed>>, grouped: array<string, array<int, array<string, mixed>>>}
     */
    public function permissionPayloadForRole(Role $role): array
    {
        $capabilities = $role->capabilities;
        $permissions = array();

        foreach (config('permissions.abilities', array()) as $key => $meta) {
            $permissions[] = array(
                'key' => $key,
                'label' => $meta['label'] ?? $key,
                'group' => $meta['group'] ?? 'Other',
                'checked' => in_array($key, $capabilities, true),
            );
        }

        $grouped = array();
        foreach ($permissions as $permission) {
            $grouped[$permission['group']][] = $permission;
        }

        return array(
            'role' => array(
                'id' => $role->id,
                'name' => $role->name,
                'is_protected' => $this->isProtectedRole($role),
                'users_count' => $role->users()->count(),
            ),
            'permissions' => $permissions,
            'grouped' => $grouped,
        );
    }

    /**
     * @param array<int, string> $keys
     */
    public function syncCapabilities(Role $role, array $keys): Role
    {
        $validKeys = $this->allAbilityKeys();
        $keys = array_values(array_unique(array_intersect($keys, $validKeys)));

        if ($this->isProtectedRole($role)) {
            $keys = $this->mergeRequiredAdminCapabilities($keys);
        }

        $role->capabilities = $keys;
        $role->save();

        return $role->fresh();
    }

    public function duplicateRole(string $name, ?int $sourceRoleId = null): Role
    {
        $capabilities = array();

        if ($sourceRoleId !== null) {
            $source = Role::findOrFail($sourceRoleId);
            if ($this->isStaffRole($source)) {
                $capabilities = $source->capabilities;
            }
        }

        return Role::create(array(
            'name' => $name,
            'capabilities' => $capabilities,
        ));
    }

    public function isProtectedRole(Role $role): bool
    {
        return $role->name === 'admin';
    }

    public function isStaffRole(Role $role): bool
    {
        return ! in_array($role->name, config('permissions.excluded_roles', array('customer')), true);
    }

    /**
     * @param array<int, string> $keys
     * @return array<int, string>
     */
    public function mergeRequiredAdminCapabilities(array $keys): array
    {
        $required = config('permissions.admin_required', array());

        return array_values(array_unique(array_merge($keys, $required)));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function assertCanUpdateCapabilities(Role $role, array $keys): void
    {
        if (! $this->isStaffRole($role)) {
            throw new InvalidArgumentException('This role cannot be managed from the permissions UI.');
        }

        if ($this->isProtectedRole($role)) {
            $required = config('permissions.admin_required', array());
            $missing = array_diff($required, $keys);

            if ($missing !== array()) {
                throw new InvalidArgumentException(
                    'The admin role must keep: ' . implode(', ', $missing)
                );
            }
        }
    }

    public function seedStaffRoleCapabilities(): void
    {
        foreach ($this->defaultMatrix() as $roleName => $capabilities) {
            $role = Role::where('name', $roleName)->first();

            if ($role !== null) {
                $role->capabilities = $capabilities;
                $role->save();
            }
        }
    }

    public function seedCustomerCapabilities(): void
    {
        $role = Role::where('name', 'customer')->first();

        if ($role !== null) {
            $role->capabilities = $this->customerCapabilities();
            $role->save();
        }
    }
}
