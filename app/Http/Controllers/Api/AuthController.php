<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Account is deactivated.',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'             => $user->id,
                'user_type'      => $user->user_type,
                'display_name'   => $user->display_name,
                'email'          => $user->email,
                'games_expertise'=> $user->games_expertise,
                'is_verified'    => $user->is_verified,
                'profile_image_url' => $user->profile_image_url,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id'                => $user->id,
            'user_type'         => $user->user_type,
            'display_name'      => $user->display_name,
            'email'             => $user->email,
            'bio'               => $user->bio,
            'games_expertise'   => $user->games_expertise,
            'is_verified'       => $user->is_verified,
            'profile_image_url' => $user->profile_image_url,
            'is_active'         => $user->is_active,
            'joined_at'         => $user->created_at,
        ]);
    }
}