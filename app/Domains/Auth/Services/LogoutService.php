<?php

namespace App\Domains\Auth\Services;

use Illuminate\Support\Facades\Auth;

class LogoutService
{
    public function logout(): void
    {
        Auth::guard('api')->logout();
    }
}
