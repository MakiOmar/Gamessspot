<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
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
        $excluded = config('permissions.excluded_roles', array('customer'));

        return array(
            'name' => array(
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'name'),
                Rule::notIn($excluded),
            ),
            'duplicate_from_role_id' => array(
                'nullable',
                'integer',
                Rule::exists('roles', 'id')->whereNotIn('name', $excluded),
            ),
        );
    }
}
