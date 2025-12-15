<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'usd_balance' => $this->balance,
            'assets' => AssetResource::collection(
                $this->assets()
                    ->orderBy('symbol')
                    ->get(['symbol', 'amount', 'locked_amount'])
            )->resolve(),
        ];
    }
}

