<?php

namespace App\Domains\Auth\Http\Controllers;

use App\Application\Http\Resources\UnauthorizedResource;
use App\Domains\Auth\Exceptions\TokenNotFoundException;
use App\Domains\Auth\Http\Requests\RefreshRequest;
use App\Domains\Auth\Http\Resources\TokenResource;
use App\Domains\Auth\Services\RefreshService;

class RefreshController
{
    public function __construct(
        private RefreshService $refreshService,
    ) {}

    public function __invoke(RefreshRequest $request): UnauthorizedResource|TokenResource
    {
        try {
            $refreshedTokenDto = $this->refreshService->refresh();
        } catch (TokenNotFoundException) {
            return new UnauthorizedResource;
        }

        return new TokenResource($refreshedTokenDto);
    }
}
