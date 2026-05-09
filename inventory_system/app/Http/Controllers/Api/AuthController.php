<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role ?? 'staff',
        ]);
 
        $token = $user->createToken('api-token', ['*'])->plainTextToken;
 
        return response()->json([
            'message' => 'User registered successfully.',
            'data'    => [
                'user'  => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
 
        $user = Auth::user();
 
        if (! $user->is_active) {
            Auth::logout();
            return response()->json(['message' => 'Your account has been deactivated.'], 403);
        }
 
        // Revoke previous tokens (single session)
        $user->tokens()->delete();
        $token = $user->createToken('api-token', ['*'])->plainTextToken;
 
        return response()->json([
            'message' => 'Login successful.',
            'data'    => [
                'user'       => new UserResource($user),
                'token'      => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }
 
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
 
        return response()->json(['message' => 'Logged out successfully.']);
    }
 
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($request->user()),
        ]);
    }
}
