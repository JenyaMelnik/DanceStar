<?php

declare(strict_types=1);

namespace App\Domains\Auth\DTO;

readonly class LoginDto
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
        ];
    }
}
