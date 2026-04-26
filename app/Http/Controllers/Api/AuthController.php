<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\RateLimiter;
use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
public function register(Request $request)
{
    $key = 'register:' . $request->ip();

    if (RateLimiter::tooManyAttempts($key, 3)) {
        $seconds = RateLimiter::availableIn($key);
        return response()->json([
            'message'     => 'Too many registration attempts. Please try again later.',
            'retry_after' => $seconds,
        ], 429);
    }

    $request->validate([
        'username' => 'required|string|max:255|unique:users,username|alpha_num',
        'email'    => 'required|email',
        'password' => 'required|string|min:8|confirmed',
    ]);

    // Check if email already exists
    $existingUser = User::where('email', $request->email)->first();
    if ($existingUser) {
        if (!$existingUser->isEmailVerified()) {
            return response()->json([
                'message' => 'The email you\'re trying to register is already existing but unverified.',
                'email'   => $request->email,
            ], 422);
        } else {
            return response()->json([
                'message' => 'The email address is already in use.',
            ], 422);
        }
    }

    RateLimiter::hit($key, 60);

    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    $user = User::create([
        'username'                     => $request->username,
        'display_name'                 => $request->username,
        'email'                        => $request->email,
        'password'                     => Hash::make($request->password),
        'user_type'                    => 'pilot',
        'is_active'                    => true,
        'is_verified'                  => false,
        'verification_code'            => $code,
        'verification_code_expires_at' => now()->addMinutes(10),
        'verification_code_sent_at'    => now(),
    ]);

    Mail::to($user->email)->send(new VerificationCodeMail($code, $user->username));

    return response()->json([
        'message' => 'Registration successful. Please check your email for the verification code.',
        'email'   => $user->email,
    ], 201);
}

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        if ($user->isEmailVerified()) {
            return response()->json([
                'message' => 'Email is already verified.',
            ], 422);
        }

        if ($user->verification_code !== $request->code) {
            return response()->json([
                'message' => 'Invalid verification code.',
            ], 422);
        }

        if (now()->isAfter($user->verification_code_expires_at)) {
            return response()->json([
                'message' => 'Verification code has expired. Please request a new one.',
            ], 422);
        }

        $user->update([
            'email_verified_at'            => now(),
            'verification_code'            => null,
            'verification_code_expires_at' => null,
            'verification_code_sent_at'    => null,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Email verified successfully.',
            'token'   => $token,
            'user'    => [
                'id'           => $user->id,
                'username'     => $user->username,
                'display_name' => $user->display_name,
                'email'        => $user->email,
                'user_type'    => $user->user_type,
            ],
        ]);
    }
public function resendCode(Request $request)
{
    $request->validate([
        'email' => 'required|email',
    ]);

    $key = 'resend:' . $request->email . ':' . $request->ip();

    if (RateLimiter::tooManyAttempts($key, 2)) {
        $seconds = RateLimiter::availableIn($key);
        return response()->json([
            'message'     => 'Too many resend attempts. Please try again later.',
            'retry_after' => $seconds,
        ], 429);
    }

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json([
            'message' => 'User not found.',
        ], 404);
    }

    if ($user->isEmailVerified()) {
        return response()->json([
            'message' => 'Email is already verified.',
        ], 422);
    }

    // Enforce 5 minute resend limit
    if ($user->verification_code_sent_at &&
        now()->diffInMinutes($user->verification_code_sent_at) < 5) {
        $waitSeconds = 300 - now()->diffInSeconds($user->verification_code_sent_at);
        return response()->json([
            'message'      => 'Please wait before requesting a new code.',
            'wait_seconds' => $waitSeconds,
        ], 429);
    }

    RateLimiter::hit($key, 300);

    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    $user->update([
        'verification_code'            => $code,
        'verification_code_expires_at' => now()->addMinutes(10),
        'verification_code_sent_at'    => now(),
    ]);

    Mail::to($user->email)->send(new VerificationCodeMail($code, $user->username));

    return response()->json([
        'message' => 'Verification code resent. Please check your email.',
    ]);
}

public function login(Request $request)
{
    $request->validate([
        'email_or_username' => 'required|string',
        'password'          => 'required|string',
    ]);

    $key = 'login:' . $request->email_or_username . ':' . $request->ip();

    if (RateLimiter::tooManyAttempts($key, 5)) {
        $seconds = RateLimiter::availableIn($key);
        return response()->json([
            'message'     => 'Too many login attempts. Please try again later.',
            'retry_after' => $seconds,
        ], 429);
    }

    $user = User::where('email', $request->email_or_username)
        ->orWhere('username', $request->email_or_username)
        ->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        RateLimiter::hit($key, 60);
        return response()->json([
            'message' => 'Invalid credentials.',
        ], 401);
    }

    if (!$user->is_active) {
        return response()->json([
            'message' => 'Account is deactivated.',
        ], 403);
    }

    RateLimiter::clear($key);

    // Allow login for unverified users - frontend will redirect to verify-email
    if (!$user->isEmailVerified()) {
        return response()->json([
            'message' => 'Email not verified. Please verify your email.',
            'email'   => $user->email,
            'unverified' => true,
            'user'    => [
                'id'                => $user->id,
                'username'          => $user->username,
                'user_type'         => $user->user_type,
                'display_name'      => $user->display_name,
                'email'             => $user->email,
                'games_expertise'   => $user->games_expertise,
                'is_verified'       => $user->is_verified,
                'profile_image_url' => $user->profile_image_url,
            ],
        ], 200);
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user'  => [
            'id'                => $user->id,
            'username'          => $user->username,
            'user_type'         => $user->user_type,
            'display_name'      => $user->display_name,
            'email'             => $user->email,
            'games_expertise'   => $user->games_expertise,
            'is_verified'       => $user->is_verified,
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
            'username'          => $user->username,
            'user_type'         => $user->user_type,
            'display_name'      => $user->display_name,
            'email'             => $user->email,
            'bio'               => $user->bio,
            'games_expertise'   => $user->games_expertise,
            'is_verified'       => $user->is_verified,
            'profile_image_url' => $user->profile_image_url,
            'is_active'         => $user->is_active,
            'joined_at'         => $user->created_at,
            'fingerprint_enrolled' => $user->fingerprint_enrolled,
        ]);
    }

    public function enrollFingerprint(Request $request)
    {
        $user = $request->user();

        $user->update([
            'fingerprint_enrolled' => true,
        ]);

        return response()->json([
            'message' => 'Fingerprint enrolled successfully.',
            'fingerprint_enrolled' => true,
        ]);
    }

    public function loginWithFingerprint(Request $request)
    {
        $request->validate([
            'email_or_username' => 'required|string',
        ]);

        $key = 'login:' . $request->email_or_username . ':' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message'     => 'Too many login attempts. Please try again later.',
                'retry_after' => $seconds,
            ], 429);
        }

        $user = User::where('email', $request->email_or_username)
            ->orWhere('username', $request->email_or_username)
            ->first();

        if (!$user) {
            RateLimiter::hit($key, 60);
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        if (!$user->fingerprint_enrolled) {
            return response()->json([
                'message' => 'Fingerprint not enrolled for this account. To enroll go to your Profile Management',
            ], 422);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Account is deactivated.',
            ], 403);
        }

        RateLimiter::clear($key);

        // Allow login for unverified users - frontend will redirect to verify-email
        if (!$user->isEmailVerified()) {
            return response()->json([
                'message' => 'Email not verified. Please verify your email.',
                'email'   => $user->email,
                'unverified' => true,
                'user'    => [
                    'id'                => $user->id,
                    'username'          => $user->username,
                    'user_type'         => $user->user_type,
                    'display_name'      => $user->display_name,
                    'email'             => $user->email,
                    'games_expertise'   => $user->games_expertise,
                    'is_verified'       => $user->is_verified,
                    'profile_image_url' => $user->profile_image_url,
                ],
            ], 200);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'                => $user->id,
                'username'          => $user->username,
                'user_type'         => $user->user_type,
                'display_name'      => $user->display_name,
                'email'             => $user->email,
                'games_expertise'   => $user->games_expertise,
                'is_verified'       => $user->is_verified,
                'profile_image_url' => $user->profile_image_url,
            ],
        ]);
    }

    public function disableFingerprint(Request $request)
    {
        $user = $request->user();

        $user->update([
            'fingerprint_enrolled' => false,
        ]);

        return response()->json([
            'message' => 'Fingerprint disabled.',
            'fingerprint_enrolled' => false,
        ]);
    }
}