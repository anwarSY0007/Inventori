<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->user()->id)
            ],
            'phone' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('users')->ignore($this->user()->id)
            ],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif', 'max:2048'],
        ];
    }
    public function messages(): array
    {
        return [
            'email.unique' => 'Email already taken by another user',
            'phone.unique' => 'Phone number already taken by another user',
            'avatar.image' => 'Avatar must be an image file',
            'avatar.max' => 'Avatar size must not exceed 2MB',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        if ($this->hasFile('avatar')) {
            $path = $this->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $path;
        }

        return $validated;
    }
}
