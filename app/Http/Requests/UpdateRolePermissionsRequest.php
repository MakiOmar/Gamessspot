<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage-options') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $validKeys = array_keys(config('permissions.abilities', array()));

        return array(
            'permissions' => array('required', 'array'),
            'permissions.*' => array('string', Rule::in($validKeys)),
        );
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var Role|null $role */
            $role = $this->route('role');

            if ($role === null) {
                return;
            }

            $service = app(RolePermissionService::class);

            if (! $service->isStaffRole($role)) {
                $validator->errors()->add('role', 'This role cannot be updated.');

                return;
            }

            try {
                $service->assertCanUpdateCapabilities(
                    $role,
                    $this->input('permissions', array())
                );
            } catch (\InvalidArgumentException $exception) {
                $validator->errors()->add('permissions', $exception->getMessage());
            }
        });
    }
}
