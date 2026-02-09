<?php

namespace App\Domains\Auth\Http\Controllers;

use App\Application\Http\Resources\UnauthorizedResource;
use App\Domains\Auth\Exceptions\TokenNotFoundException;
use App\Domains\Auth\Http\Requests\LoginRequest;
use App\Domains\Auth\Http\Resources\TokenResource;
use App\Domains\Auth\Services\LoginService;

class LoginController
{
    public function __construct(
       private LoginService $loginService,
    ) {
    }

    public function __invoke(LoginRequest $request): UnauthorizedResource|TokenResource
    {
        try {
            $tokenDto = $this->loginService->login($request->getDTO());
        } catch (TokenNotFoundException) {
            return new UnauthorizedResource();
        }

        return new TokenResource($tokenDto);
    }
}
