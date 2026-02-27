<?php

namespace App\Domains\Auth\Services;

use App\Domains\Auth\DTO\TokenDto;
use App\Domains\Auth\Exceptions\TokenNotFoundException;
use Illuminate\Support\Facades\Auth;

class RefreshService
{
    public function refresh(): TokenDto
    {
        /** @var string|null $token */
        if (! $token = Auth::guard('api')->refresh()) {
            throw new TokenNotFoundException;
        }

        /** @var int $expiresIn */
        $expiresIn = Auth::guard('api')->factory()->getTTL() * 60;

        return new TokenDto($token, $expiresIn);
    }
}
