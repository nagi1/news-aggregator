<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserPreferenceRequest;
use App\Http\Resources\UserPreferenceResource;

class UserPreferenceController extends Controller
{
    public function index()
    {
        return UserPreferenceResource::collection(auth()->user()->preferences ?? []);
    }

    public function update(UpdateUserPreferenceRequest $request)
    {
        $request->user()->update([
            'preferences' => array_merge(
                $request->user()->preferences ?? [],
                $request->validated()
            ),
        ]);

        return UserPreferenceResource::make(auth()->user()->preferences ?? []);
    }
}
