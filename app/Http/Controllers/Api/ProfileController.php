<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        $totalGrinds     = $user->grinds()->count();
        $completedGrinds = $user->grinds()->where('status', 'completed')->count();
        $activeGrinds    = $user->grinds()->where('status', 'in_progress')->count();

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
            'stats'             => [
                'total_grinds'     => $totalGrinds,
                'completed_grinds' => $completedGrinds,
                'active_grinds'    => $activeGrinds,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'display_name'    => 'sometimes|string|max:255',
            'bio'             => 'sometimes|nullable|string',
            'games_expertise' => 'sometimes|array',
            'games_expertise.*' => 'in:CODM,MLBB,Valorant',
        ]);

        $user = $request->user();

        $user->update($request->only([
            'display_name',
            'bio',
            'games_expertise',
        ]));

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user'    => [
                'id'              => $user->id,
                'display_name'    => $user->display_name,
                'bio'             => $user->bio,
                'games_expertise' => $user->games_expertise,
            ],
        ]);
    }
}