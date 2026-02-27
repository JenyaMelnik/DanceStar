<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services;

use App\Domains\Auth\DTO\LoginDto;
use App\Domains\Auth\DTO\TokenDto;
use App\Domains\Auth\Exceptions\TokenNotFoundException;
use Illuminate\Support\Facades\Auth;

class LoginService
{
    public function login(LoginDto $getDTO): TokenDto
    {
        /** @var string|null $token */
        if (! $token = Auth::guard('api')->attempt($getDTO->toArray())) {
            throw new TokenNotFoundException;
        }

        /** @var int $expiresIn */
        $expiresIn = Auth::guard('api')->factory()->getTTL() * 60;

        return new TokenDto($token, $expiresIn);
    }
}
