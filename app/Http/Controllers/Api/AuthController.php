<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! $token = Auth::guard('api')->attempt($credentials)) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * @return JsonResponse
     */
    public function logout()
    {
        Auth::guard('api')->logout();

        return response()->json(['message' => 'Logged out']);
    }

    /**
     * @return JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(
            Auth::guard('api')->refresh()
        );
    }

    /**
     * @return JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
        ]);
    }
}
