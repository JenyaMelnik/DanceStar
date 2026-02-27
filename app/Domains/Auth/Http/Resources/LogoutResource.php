<?php

namespace App\Domains\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogoutResource extends JsonResource
{
    public function __construct()
    {
        parent::__construct(null);
    }

    public function toArray(Request $request): array
    {
        return [
            'message' => 'Logged out',
        ];
    }
}
