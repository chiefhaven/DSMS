<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $directory = config('bank_directory', []);
        $bankName = $this->input('bank_name');
        $branchNames = is_string($bankName) && isset($directory[$bankName])
            ? array_keys($directory[$bankName]['branches'] ?? [])
            : [];

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
            'username' => 'required',
            'employment_date' => 'nullable|date',
            'employment_type' => 'nullable|in:Full time,Contract,Part-time',
            'end_date' => 'nullable|date',
            'terms_of_employment' => 'nullable|string',
            'contract_signed' => 'nullable|boolean',
            'contract_signed_at' => 'nullable|date',
            'paye' => 'nullable|boolean',
            'status' => ['required', Rule::in(['Active', 'Pending', 'Suspended', 'Contract ended'])],
            'bank_name' => ['nullable', 'string', Rule::in(array_keys($directory)), 'required_with:bank_branch,bank_account_name,bank_account_number,bank_account_type'],
            'bank_branch' => ['nullable', 'string', Rule::in($branchNames), 'required_with:bank_name,bank_account_name,bank_account_number,bank_account_type'],
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_account_type' => 'nullable|string|max:100',
        ];
    }
}
