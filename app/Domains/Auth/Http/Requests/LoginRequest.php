<?php

namespace App\Domains\Auth\Http\Requests;

use App\Domains\Auth\DTO\LoginDto;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
            ],
            'password' => ['required'],
        ];
    }

    public function getDTO(): LoginDto
    {
        return new LoginDto(
            email: $this->validated('email'),
            password: $this->validated('password'),
        );
    }
}
