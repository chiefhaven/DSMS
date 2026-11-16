<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdministratorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $requestedRole = $this->input('role');
        if ($requestedRole !== 'superAdmin') {
            return true;
        }

        $administrator = $this->route('administrator');
        $currentRoles = $administrator?->user?->getRoleNames()->all() ?? [];

        return in_array('superAdmin', $currentRoles, true)
            || $this->user()?->can('manage system');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            //
        ];
    }
}
