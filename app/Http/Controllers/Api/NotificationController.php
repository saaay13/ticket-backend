<?php

namespace App\Http\Controllers\Api;

use App\Models\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Resources\NotificationResource;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->query('sort', 'created_at');
        $order = $request->query('order', 'desc');

        // Seguridad: Solo obtener notificaciones para el usuario actual
        $notifications = $request->user()->notifications()
            ->with(['ticket'])
            ->orderBy($sort, $order)
            ->get();

        return NotificationResource::collection($notifications);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'ticket_id' => 'nullable|exists:tickets,id',
            'type' => 'required|string|max:50',
            'title' => 'required|string|max:200',
            'content' => 'nullable|string',
            'read' => 'sometimes|boolean',
            'metadata' => 'nullable|array',
        ], [
            'user_id.required' => 'The user is required.',
            'user_id.exists' => 'The user does not exist.',
            'type.required' => 'The type is required.',
            'title.required' => 'The title is required.',
        ]);

        $notification = Notification::create($validated);
        
        return (new NotificationResource($notification->load(['ticket'])))
            ->additional(['message' => 'Notification created successfully']);
    }

    public function show(Notification $notification)
    {
        return new NotificationResource($notification->load(['ticket']));
    }

    public function update(Request $request, Notification $notification)
    {
        $validated = $request->validate([
            'read' => 'sometimes|boolean',
            'title' => 'sometimes|string|max:200',
            'content' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $notification->update($validated);
        
        return (new NotificationResource($notification))
            ->additional(['message' => 'Notification updated successfully']);
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();
        return response()->json(['message' => 'Notification deleted successfully'], 200);
    }
}
