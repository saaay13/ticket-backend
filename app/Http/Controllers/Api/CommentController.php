<?php

namespace App\Http\Controllers\Api;

use App\Models\Comment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

use App\Http\Resources\CommentResource;

class CommentController extends Controller
{
    // Listar todos los comentarios
    public function index(Request $request)
    {
        $sort = $request->query('sort', 'created_at');
        $order = $request->query('order', 'desc');

        $comments = Comment::with(['ticket', 'user'])->orderBy($sort, $order)->get();
        return CommentResource::collection($comments)->response()->getData(true)['data'];
    }

    // Crear un nuevo comentario
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'content' => 'required|string',
            'metadata' => 'nullable|array',
            'user_id' => 'nullable|exists:users,id'
        ], [
            'ticket_id.required' => 'El ticket es obligatorio.',
            'ticket_id.exists' => 'El ticket seleccionado no existe.',
            'content.required' => 'El contenido del comentario no puede estar vacío.',
        ]);

        if (empty($validated['user_id'])) {
            $validated['user_id'] = Auth::id() ?? $request->user()?->id;
        }
        
        if (empty($validated['user_id'])) {
             return response()->json(['message' => 'Usuario no autenticado'], 401);
        }

        $comment = Comment::create($validated);
        return (new CommentResource($comment->load(['ticket', 'user'])))
            ->additional(['message' => 'Comentario creado con éxito']);
    }

    // Ver detalle de un comentario
    public function show(Comment $comment)
    {
        return new CommentResource($comment->load(['ticket', 'user']));
    }

    // Actualizar un comentario
    public function update(Request $request, Comment $comment)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'metadata' => 'nullable|array',
        ], [
            'content.required' => 'El contenido no puede estar vacío.',
        ]);

        $comment->update($validated);
        return (new CommentResource($comment->load('user')))
            ->additional(['message' => 'Comentario actualizado con éxito']);
    }

    // Eliminar un comentario
    public function destroy(Comment $comment)
    {
        $comment->delete();
        return response()->json(['message' => 'Comentario eliminado con éxito']);
    }
}
