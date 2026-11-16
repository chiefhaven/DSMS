<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateStaffAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->routeIs('instructors.account.update')
            ? 'manage instructor accounts'
            : 'manage administrator accounts';

        return $this->user()?->can($permission) ?? false;
    }

    public function rules(): array
    {
        $profile = $this->route('instructor') ?? $this->route('administrator');
        $user = $profile->user()->firstOrFail();

        return [
            'name' => ['sometimes', 'string', Rule::in([$user->name])],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.in' => 'Usernames are read-only.',
        ];
    }
}
