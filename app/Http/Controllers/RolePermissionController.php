<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRolePermissionsRequest;
use App\Models\Role;
use App\Services\RolePermissionService;
use Illuminate\Http\JsonResponse;
class RolePermissionController extends Controller
{
    public function __construct(
        protected RolePermissionService $rolePermissionService
    ) {
    }

    public function index()
    {
        $roles = $this->rolePermissionService->staffRoles();

        return view('manager.role-permissions', compact('roles'));
    }

    public function data(): JsonResponse
    {
        $roles = $this->rolePermissionService->staffRoles()->map(function (Role $role) {
            return array(
                'id' => $role->id,
                'name' => $role->name,
                'is_protected' => $this->rolePermissionService->isProtectedRole($role),
            );
        });

        return response()->json(array(
            'roles' => $roles,
        ));
    }

    public function show(Role $role): JsonResponse
    {
        if (! $this->rolePermissionService->isStaffRole($role)) {
            return response()->json(array(
                'message' => 'This role cannot be managed.',
            ), 404);
        }

        return response()->json(
            $this->rolePermissionService->permissionPayloadForRole($role)
        );
    }

    public function update(UpdateRolePermissionsRequest $request, Role $role): JsonResponse
    {
        if (! $this->rolePermissionService->isStaffRole($role)) {
            return response()->json(array(
                'message' => 'This role cannot be managed.',
            ), 422);
        }

        $role = $this->rolePermissionService->syncCapabilities(
            $role,
            $request->input('permissions', array())
        );

        return response()->json(array(
            'message' => 'Permissions updated successfully.',
            'data' => $this->rolePermissionService->permissionPayloadForRole($role),
        ));
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->rolePermissionService->duplicateRole(
            $request->input('name'),
            $request->input('duplicate_from_role_id')
        );

        return response()->json(array(
            'message' => 'Role created successfully.',
            'data' => $this->rolePermissionService->permissionPayloadForRole($role),
        ), 201);
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($this->rolePermissionService->isProtectedRole($role)) {
            return response()->json(array(
                'message' => 'The admin role cannot be deleted.',
            ), 422);
        }

        if (! $this->rolePermissionService->isStaffRole($role)) {
            return response()->json(array(
                'message' => 'This role cannot be deleted.',
            ), 422);
        }

        if ($role->users()->exists()) {
            return response()->json(array(
                'message' => 'Cannot delete a role that is assigned to users.',
            ), 422);
        }

        $role->delete();

        return response()->json(array(
            'message' => 'Role deleted successfully.',
        ));
    }
}
