<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()?->can('edit roles') ?? false;
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:50', Rule::unique('roles', 'name')->ignore($this->route('role')->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }
}
