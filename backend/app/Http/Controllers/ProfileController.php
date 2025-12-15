<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Http\Resources\ProfileResource;

class ProfileController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ProfileRequest $request): array
    {
        return ProfileResource::make($request->user())->resolve();
    }
}
