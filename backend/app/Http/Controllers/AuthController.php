<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): array
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        $tokenName = $credentials['device_name'] ?? 'api';
        $token = $user->createToken($tokenName)->plainTextToken;

        return [
            'token' => $token,
        ];
    }

    public function logout(Request $request): array
    {
        $request->user()->currentAccessToken()?->delete();

        return [
            'ok' => true,
        ];
    }
}
