<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdministratorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->input('role') !== 'superAdmin' || $this->user()?->can('manage system');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'first_name' => 'required',
            'sir_name' => 'required',
            'email' => 'required|email|unique:users,email',
            'address' => 'required',
            'gender' => 'required',
            'date_of_birth' => 'required',
            'district' => 'required',
            'role' => 'required|in:admin,financeAdmin,superAdmin',
            'phone' => 'required',
            'password' => 'required|min:8',
            'username' => 'required'
        ];
    }
}
