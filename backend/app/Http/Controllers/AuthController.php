<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\LogoutRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): array
    {
        $credentials = $request->validated();

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
            'user' => [
                'id' => $user->id,
            ],
        ];
    }

    public function logout(LogoutRequest $request): array
    {
        $request->user()->currentAccessToken()?->delete();

        return [
            'ok' => true,
        ];
    }
}
