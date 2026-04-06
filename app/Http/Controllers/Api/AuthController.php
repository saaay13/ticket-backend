<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.'
            ], 401);
        }

        // Asegurar que el usuario tenga las relaciones necesarias cargadas para la respuesta
        $user->load('department');

        return response()->json([
            'user' => (new \App\Http\Resources\UserResource($user))->resolve(),
            'token' => $user->createToken('auth-token')->plainTextToken,
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request)
    {
        return new \App\Http\Resources\UserResource($request->user()->load('department'));
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json(['message' => 'No encontramos un usuario con ese correo electrónico.'], 404);
        }

        return response()->json(['message' => 'Enlace de recuperación enviado con éxito.']);
    }

    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'Staff',
            'department_id' => $request->department_id,
            'active' => true,
        ]);

        return response()->json([
            'user' => (new \App\Http\Resources\UserResource($user->load('department')))->resolve(),
            'token' => $user->createToken('auth-token')->plainTextToken,
        ], 201);
    }
}
