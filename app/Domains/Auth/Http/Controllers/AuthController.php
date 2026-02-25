<?php

namespace App\Domains\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController
{
    //    public function login(Request $request): JsonResponse
    //    {
    //        $credentials = $request->validate([
    //            'email' => 'required|email',
    //            'password' => 'required',
    //        ]);
    //
    //        if (! $token = Auth::guard('api')->attempt($credentials)) {
    //            return response()->json([
    //                'error' => 'Unauthorized',
    //            ], 401);
    //        }
    //
    //        return $this->respondWithToken($token);
    //    }

    public function logout(): JsonResponse
    {
        Auth::guard('api')->logout();

        return response()->json(['message' => 'Logged out']);
    }

    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(
            Auth::guard('api')->refresh()
        );
    }

    protected function respondWithToken($token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
        ]);
    }
}
