<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Models\User;
use App\Models\Ticket;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\History;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TicketController extends Controller
{
    // Métricas globales para el Dashboard
    public function metrics()
    {
        try {
            // Rendimiento IT de todos los técnicos
            $teamMetrics = User::whereIn(DB::raw('LOWER(role)'), ['admin', 'agent', 'agente', 'staff'])
                ->withCount([
                    'ticketsAssigned as total',
                    'ticketsAssigned as resolved' => fn ($q) => $q->whereIn('status', ['resolved', 'closed', 'Resolved', 'Closed']),
                    'ticketsAssigned as open' => fn ($q) => $q->whereIn('status', ['open', 'in_progress', 'pending', 'Open', 'In Progress', 'Pending'])
                ])
                ->get()
                ->map(fn ($agent) => [
                    'id' => $agent->id,
                    'name' => "{$agent->first_name} {$agent->last_name}",
                    'avatar' => strtoupper(substr($agent->first_name, 0, 1) . substr($agent->last_name, 0, 1)),
                    'open' => (int) $agent->open,
                    'resolved' => (int) $agent->resolved,
                    'total' => (int) $agent->total,
                    'percentage' => round(($agent->resolved / ($agent->total ?: 1)) * 100, 1)
                ])
                ->sortByDesc('percentage')
                ->values();

            // Estadísticas rápidas
            $stats = [
                'total' => Ticket::count(),
                'open' => Ticket::whereIn('status', ['open', 'Open'])->count(),
                'in_progress' => Ticket::whereIn('status', ['in_progress', 'pending', 'In Progress', 'Pending'])->count(),
                'resolved' => Ticket::whereIn('status', ['resolved', 'closed', 'Resolved', 'Closed'])->count(),
                'critical' => Ticket::where('details->priority', 'critical')->count()
            ];

            // Porcentaje por categorías
            $categories = Category::withCount('tickets')->get()->map(fn($cat) => [
                'name' => $cat->name,
                'value' => (int) $cat->tickets_count
            ]);

            // Datos semanales (Gráfico)
            $weeklyData = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $fullDate = $date->format('Y-m-d');
                
                $weeklyData[] = [
                    'day' => $date->isoFormat('ddd'),
                    'opened' => Ticket::whereDate('created_at', $fullDate)->count(),
                    'resolved' => Ticket::whereIn('status', ['resolved', 'closed', 'Resolved', 'Closed'])
                                       ->whereDate('updated_at', $fullDate)->count(),
                ];
            }

            return response()->json([
                'team' => $teamMetrics, // Devolvemos todo el equipo para que aparezcan en el Directorio
                'stats' => $stats,
                'categories' => $categories,
                'weekly' => $weeklyData
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al calcular métricas'], 500);
        }
    }

    /**
     * Listar tickets filtrados por rol y departamento.
     */
    public function index()
    {
        /** @var \App\Models\User|null $user */
        $user = request()->user();
        
        $sort = request()->query('sort', 'create_at');
        $order = request()->query('order', 'desc');

        $query = Ticket::with(['requester', 'assignedTo', 'category'])
            ->where('status', '!=', 'deleted');

        // Staff solo ve tickets de su departamento
        if ($user && in_array(strtolower($user->role), ['staff', 'requester'])) {
            $query->whereHas('requester', function ($q) use ($user) {
                $q->where('department_id', $user->department_id);
            });
        }

        $tickets = $query->orderBy($sort, $order)->get();

        return TicketResource::collection($tickets);
    }

    public function store(StoreTicketRequest $request)
    {
        $ticket = Ticket::create($request->validated());

        // Registrar en historial
        History::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => 'Ticket Creado',
            'changes' => $ticket->only(['title', 'status', 'category_id']),
        ]);

        return (new TicketResource($ticket->load(['requester', 'assignedTo', 'category'])))
            ->additional(['message' => 'Ticket creado con éxito']);
    }

    // Detalle de un ticket individual
    public function show($id)
    {
        $ticket = Ticket::with(['requester', 'assignedTo', 'category'])->find($id);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket no encontrado'], 404);
        }

        return new TicketResource($ticket);
    }

    public function update(UpdateTicketRequest $request, $id)
    {
        $ticket = Ticket::find($id);
        if (!$ticket) return response()->json(['message' => 'Ticket no encontrado'], 404);

        $original = $ticket->getOriginal();
        $validated = $request->validated();

        if (isset($validated['details'])) {
            $validated['details'] = array_merge($ticket->details ?? [], $validated['details']);
        }

        $ticket->update($validated);
        $changes = $ticket->getChanges();

        if (!empty($changes)) {
            History::create([
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'action' => 'Ticket Actualizado',
                'changes' => [
                    'antes' => array_intersect_key($original, $changes),
                    'despues' => $changes
                ],
            ]);
        }

        return (new TicketResource($ticket->load(['requester', 'assignedTo', 'category'])))
            ->additional(['message' => 'Ticket actualizado con éxito']);
    }

    public function destroy($id)
    {
        $ticket = Ticket::find($id);
        if (!$ticket) return response()->json(['message' => 'Ticket no encontrado'], 404);

        if ($ticket->status === 'deleted') {
            return response()->json(['message' => 'El ticket ya ha sido eliminado'], 400);
        }

        $ticket->update(['status' => 'deleted']);
        
        History::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => 'Ticket Eliminado',
            'changes' => ['status' => 'deleted']
        ]);

        return response()->json(['message' => 'Ticket eliminado con éxito']);
    }

    // Listar tickets eliminados
    public function trash()
    {
        $tickets = Ticket::with(['requester', 'assignedTo', 'category'])
            ->where('status', 'deleted')
            ->orderByDesc('updated_at')
            ->get();

        return TicketResource::collection($tickets);
    }

    // Restaurar ticket eliminado
    public function restore($id)
    {
        $ticket = Ticket::find($id);
        if (!$ticket) return response()->json(['message' => 'Ticket no encontrado'], 404);

        if ($ticket->status !== 'deleted') {
            return response()->json(['message' => 'Este ticket no está eliminado'], 400);
        }

        $ticket->update(['status' => 'open']);

        History::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => 'Ticket Restaurado',
            'changes' => ['status' => 'open']
        ]);

        return response()->json(['message' => 'Ticket reactivado con éxito']);
    }

    // Comentarios de un ticket específico
    public function comments($id)
    {
        $ticket = Ticket::find($id);
        if (!$ticket) return response()->json(['message' => 'Ticket no encontrado'], 404);

        $comments = $ticket->comments()->with('user')->orderByDesc('created_at')->get();
        return \App\Http\Resources\CommentResource::collection($comments)->response()->getData(true)['data'];
    }
}
