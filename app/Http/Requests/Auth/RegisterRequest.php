<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->has('username')) {
            $this->merge([
                'username' => Str::lower(ltrim($this->username, '@')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            // Honeypot field (should be empty)
            'website' => ['nullable', 'prohibited'], 
            
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
        ];
    }
}
