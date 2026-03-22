<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'current_password' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!Hash::check($value, auth()->user()->password)) {
                        $fail('The current password is incorrect.');
                    }
                },
            ],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'The current password is required.',
            'password.required' => 'The new password is required.',
            'password.confirmed' => 'The new password confirmation does not match.',
            'password.min' => 'The new password must be at least 8 characters long.',
            'password.letters' => 'The new password must contain at least one letter.',
            'password.mixedCase' => 'The new password must contain at least one uppercase and one lowercase letter.',
            'password.numbers' => 'The new password must contain at least one number.',
            'password.symbols' => 'The new password must contain at least one special character.',
            'password.uncompromised' => 'The new password has appeared in a data leak. Please choose a different password.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'current_password' => 'current password',
            'password' => 'new password',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace from password fields
        $this->merge([
            'current_password' => $this->current_password ? trim($this->current_password) : null,
            'password' => $this->password ? trim($this->password) : null,
            'password_confirmation' => $this->password_confirmation ? trim($this->password_confirmation) : null,
        ]);
    }
}
