<?php

namespace App\Http\Requests\Users;

use App\Enum\RolesEnum;
use Illuminate\Foundation\Http\FormRequest;

class AssignRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only admins can assign roles
        return $this->user()->hasRole(RolesEnum::SUPER_ADMIN);
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'string', 'exists:roles,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'Role is required',
            'role.exists' => 'Role not found in the system',
        ];
    }
    public function attributes(): array
    {
        return [
            'role' => 'role name',
        ];
    }
}
