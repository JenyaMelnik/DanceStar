<?php

namespace App\Domains\Auth\Http\Controllers;

use App\Domains\Auth\Services\LogoutService;

class LogoutController
{
    public function __construct(
        private LogoutService $logoutService,
    ) {}

    public function __invoke()
    {
        $this->logoutService->logout();

        return response()->json(['message' => 'Logged out']);
    }
}
