<?php

namespace App\Http\Controllers\Api;

use App\Models\History;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Resources\HistoryResource;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->query('sort', 'created_at');
        $order = $request->query('order', 'desc');

        $history = History::with(['ticket', 'user'])
            ->orderBy($sort, $order)
            ->get();

        return HistoryResource::collection($history);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'user_id' => 'required|exists:users,id',
            'action' => 'required|string|max:50',
            'changes' => 'required|array',
            'metadata' => 'nullable|array',
        ], [
            'ticket_id.required' => 'The ticket is required.',
            'ticket_id.exists' => 'The ticket does not exist.',
            'user_id.required' => 'The user is required.',
            'user_id.exists' => 'The user does not exist.',
            'action.required' => 'The action is required.',
        ]);

        $history = History::create($validated);
        
        return (new HistoryResource($history->load(['ticket', 'user'])))
            ->additional(['message' => 'History entry created successfully']);
    }

    public function show(History $history)
    {
        return new HistoryResource($history->load(['ticket', 'user']));
    }

    public function destroy(History $history)
    {
        $history->delete();
        return response()->json(['message' => 'History entry deleted successfully'], 200);
    }
}
