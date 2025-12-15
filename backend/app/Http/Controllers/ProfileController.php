<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): array
    {
        $user = $request->user();

        return [
            'usd_balance' => $user->balance,
            'assets' => $user
                ->assets()
                ->orderBy('symbol')
                ->get(['symbol', 'amount', 'locked_amount']),
        ];
    }
}
