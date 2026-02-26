<?php

namespace App\Domains\Auth\Http\Controllers;

use App\Domains\Auth\Http\Resources\LogoutResource;
use App\Domains\Auth\Services\LogoutService;

class LogoutController
{
    public function __construct(
        private readonly LogoutService $logoutService,
    ) {}

    public function __invoke()
    {
        $this->logoutService->logout();

        return new LogoutResource(null);
    }
}
