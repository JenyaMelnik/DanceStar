<?php

declare(strict_types=1);

namespace App\Domains\Auth\DTO;

readonly class TokenDto
{
    public function __construct(
        public string $token,
        public int $expiresIn,
    ) {}
}
