<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\UserResource;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;

class UserController extends Controller
{
    // Listar usuarios con ordenamiento
    public function index(Request $request)
    {
        $sort = $request->query('sort', 'first_name');
        $order = $request->query('order', 'asc');

        // Ordenamiento estricto por nombre y luego por apellido desde la BD
        $users = User::with('department')
            ->orderBy($sort, $order)
            ->orderBy('last_name', $order)
            ->get();
            
        return UserResource::collection($users);
    }

    // Crear un nuevo usuario
    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);
    
        $user = User::create($validated);
        return (new UserResource($user->load('department')))
            ->additional(['message' => 'Usuario creado con éxito']);
    }

    // Detalle de un usuario
    public function show(User $user)
    {
        return new UserResource($user->load('department'));
    }

    // Actualizar usuario
    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        return (new UserResource($user->load('department')))
            ->additional(['message' => 'Usuario actualizado con éxito']);
    }

    // Desactivar usuario (borrado lógico)
    public function destroy(User $user)
    {
        if (! $user->active) {
            return response()->json(['message' => 'El usuario ya está inactivo'], 400);
        }

        $user->update(['active' => false]);
        return response()->json(['message' => 'Usuario desactivado con éxito']);
    }
}

