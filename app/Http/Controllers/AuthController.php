<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register a new user.
     *
     * @param Request $request The incoming HTTP request containing user details.
     * @return JsonResponse A JSON response with the access token and user details, or an error message.
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'password_confirmation' => 'required|string',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            return response()->json([
                'access_token' => $user->createToken('auth_token')->plainTextToken,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at,
                ],
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Registration failed',
            ], 500);
        }
    }

    /**
     * Log in an existing user.
     *
     * @param Request $request The incoming HTTP request containing login credentials.
     * @return JsonResponse A JSON response with the access token and user details, or an error message.
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string|min:8',
            ]);

            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'message' => 'Invalid credentials',
                ], 401);
            }

            $user = User::where('email', $request->email)->firstOrFail();

            return response()->json([
                'access_token' => $user->createToken('auth_token')->plainTextToken,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at,
                ],
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Login failed',
            ], 500);
        }
    }

    /**
     * Log out the authenticated user.
     *
     * @param Request $request The incoming HTTP request from the authenticated user.
     * @return JsonResponse A JSON response indicating success or failure.
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->tokens()->delete();
            return response()->json(null, 204);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
            ], 500);
        }
    }
}