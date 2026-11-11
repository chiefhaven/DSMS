<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateStaffAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage administration') ?? false;
    }

    public function rules(): array
    {
        $profile = $this->route('instructor') ?? $this->route('administrator');
        $user = $profile->user()->firstOrFail();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
